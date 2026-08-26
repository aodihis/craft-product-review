# Submitting a review

To submit a review, you can make a post request to `product-review/review/save` action endpoint.

A review must already exist before it can be submitted. The plugin creates them when an order reaches your configured status, and `currentUser.getWaitingToReviewItems()` lists the ones waiting.

Params

| Param | Description |
| --- | --- |
| `id` | Required. The ID of the review that will be saved. |
| `rating` | Required. The rating value for the product, a whole number from 1 to 5. |
| `comment` | Optional. The reviewer's comment for the product. HTML is sanitized before it is stored. Rejected if it is longer than `maxCharactersPerReview`. |

The customer must be signed in and must own the review.

### The simplest form that works

Plain HTML, no styling, nothing optional.

```twig
{% for review in currentUser.getWaitingToReviewItems() %}
  <form method="post">
    {{ csrfInput() }}
    {{ actionInput('product-review/review/save') }}
    {{ hiddenInput('id', review.id) }}

    <p>{{ review.product.title }}</p>

    <label>
      Rating
      <select name="rating" required>
        <option value="">Choose</option>
        {% for i in 1..5 %}
          <option value="{{ i }}">{{ i }}</option>
        {% endfor %}
      </select>
    </label>

    <label>
      Comment
      <textarea name="comment" rows="4"></textarea>
    </label>

    <button type="submit">Submit review</button>
  </form>
{% endfor %}
```

### Stars instead of a dropdown

Radio buttons, styled with CSS, keep it accessible and need no JavaScript.

```twig
<fieldset>
  <legend>Rating</legend>
  {% for i in 1..5 %}
    <label>
      <input type="radio" name="rating" value="{{ i }}" required>
      {{ i }} star{{ i > 1 ? 's' }}
    </label>
  {% endfor %}
</fieldset>
```

### Handling the response

The controller sets a flash notice and follows your `redirect` parameter. Without one, the customer stays where they are, so show the flash messages.

```twig
{% set notice = craft.app.session.getFlash('notice') %}
{% if notice %}<p class="notice">{{ notice }}</p>{% endif %}

{% set error = craft.app.session.getFlash('error') %}
{% if error %}<p class="error">{{ error }}</p>{% endif %}
```

When validation fails, the failed review is put back into the template as `review`, so you can re-render the form with the customer's input and the messages.

```twig
{% if review is defined and review.hasErrors() %}
  <ul>
    {% for message in review.getFirstErrors() %}
      <li>{{ message }}</li>
    {% endfor %}
  </ul>
{% endif %}
```

{% hint style="info" %}
Note the variable is called `review`. If your own loop also uses `review`, rename one of them to avoid confusion.
{% endhint %}

### Errors that are not validation failures

| Situation | Response |
| --- | --- |
| Not signed in | Redirect to the login page |
| Review ID does not exist | 404 |
| Review belongs to a different customer | 403 |
| Review has already been submitted | 400 |

These are deliberate errors rather than form validation, because they should not happen from a form you built correctly. They exist to stop someone editing the hidden `id` field and writing to another customer's review.

### Submitting with AJAX

Send `Accept: application/json` and the same parameters. A success returns the saved review, a failure returns the validation errors.

```js
fetch(window.location.href, {
    method: 'POST',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    body: new FormData(form),
})
    .then(function (response) { return response.json(); })
    .then(function (data) {
        if (data.errors) {
            console.log('Could not save', data.errors);
            return;
        }
        console.log('Saved', data.review);
    });
```

### A complete account page

Waiting reviews with forms, plus what the customer has already written.

```twig
{% requireLogin %}

<h1>My reviews</h1>

{% set notice = craft.app.session.getFlash('notice') %}
{% if notice %}<p class="notice">{{ notice }}</p>{% endif %}

{% set error = craft.app.session.getFlash('error') %}
{% if error %}<p class="error">{{ error }}</p>{% endif %}

<h2>Waiting for your review</h2>

{% set pending = currentUser.getWaitingToReviewItems() %}

{% if pending|length %}
  {% for item in pending %}
    <form method="post">
      {{ csrfInput() }}
      {{ actionInput('product-review/review/save') }}
      {{ redirectInput('account/reviews') }}
      {{ hiddenInput('id', item.id) }}

      <h3>{{ item.product ? item.product.title : 'Removed product' }}</h3>

      {% if item.variants|length %}
        <p>
          {%- for variant in item.variants -%}
            {{ variant.title }}{{ not loop.last ? ', ' }}
          {%- endfor -%}
        </p>
      {% endif %}

      <fieldset>
        <legend>Rating</legend>
        {% for i in 1..5 %}
          <label><input type="radio" name="rating" value="{{ i }}" required> {{ i }}</label>
        {% endfor %}
      </fieldset>

      <textarea name="comment" rows="4" placeholder="How was it?"></textarea>
      <button type="submit">Submit review</button>
    </form>
  {% endfor %}
{% else %}
  <p>Nothing waiting for review right now.</p>
{% endif %}

<h2>Already submitted</h2>

{% for item in currentUser.getReviewHistory() %}
  <article>
    <h3>{{ item.product ? item.product.title : 'Removed product' }}</h3>
    <p>{{ item.rating }} out of 5</p>
    <p>{{ item.renderComment() ?? 'No feedback given' }}</p>
    <p>Submitted {{ item.dateUpdated|date('j M Y') }}</p>
  </article>
{% else %}
  <p>You have not submitted any reviews yet.</p>
{% endfor %}
```

{% hint style="info" %}
A review can no longer be submitted once its review window has closed. See Configuration for `maxDaysToReview`.
{% endhint %}
