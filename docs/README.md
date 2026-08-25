# Product Review documentation

Product Review adds customer reviews to Craft Commerce. When an order reaches an order status you
choose, the plugin creates one empty review per product in that order. The customer then fills it in
with a rating and an optional comment, and you moderate the results from the control panel.

## Contents

1. [Installation](installation.md)
2. [Settings](settings.md)
3. [How reviews work](how-reviews-work.md)
4. [Twig reference](twig-reference.md)
5. [Building a review form](review-form.md)
6. [Displaying reviews on a product page](displaying-reviews.md)
7. [Control panel](control-panel.md)
8. [Events and extending](extending.md)
9. [PHP API reference](php-api.md)
10. [Troubleshooting](troubleshooting.md)

## Quick start

Four steps to a working review on a page.

**1. Choose the order status that starts a review.** Go to Settings, then Plugins, then Product
Review, and pick a status such as Completed. Nothing happens until this is set.

**2. Let an order reach that status.** Either place a test order and change its status in Commerce,
or wait for a real one. The plugin creates one review per product in the order, owned by the
customer.

**3. Give the customer somewhere to submit it.** In a template the customer can reach when signed
in:

```twig
{% for review in currentUser.getWaitingToReviewItems() %}
  <form method="post">
    {{ csrfInput() }}
    {{ actionInput('product-review/review/save') }}
    {{ hiddenInput('id', review.id) }}

    <p>{{ review.product.title }}</p>

    <select name="rating" required>
      {% for i in 1..5 %}<option value="{{ i }}">{{ i }}</option>{% endfor %}
    </select>

    <textarea name="comment"></textarea>
    <button type="submit">Submit review</button>
  </form>
{% endfor %}
```

**4. Show the reviews on the product page.**

```twig
{% for review in product.getReviews() %}
  <article>
    <p>Rating: {{ review.rating }} out of 5</p>
    <p>{{ review.renderComment() }}</p>
  </article>
{% endfor %}
```

That is the whole loop. The rest of this documentation covers the details, the full list of
available methods, and how to handle the cases that come up on a real store.

## Requirements

Craft CMS 5.0.0 or later, Craft Commerce 5.0.0 or later, and PHP 8.2 or later.
