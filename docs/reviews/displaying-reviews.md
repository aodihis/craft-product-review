# Displaying reviews

Everything on this page uses `product.getReviews()`, which returns submitted reviews only, so it is safe to loop over on a public page.

### The minimum

```twig
{% for review in product.getReviews() %}
  <article>
    <p>{{ review.rating }} out of 5</p>
    <p>{{ review.renderComment() ?? 'No comment' }}</p>
    <time datetime="{{ review.dateCreated|atom }}">{{ review.dateCreated|date('medium') }}</time>
  </article>
{% else %}
  <p>No reviews yet.</p>
{% endfor %}
```

### The average and a count

```twig
{% set reviews = product.getReviews() %}

{% if reviews|length %}
  <p>{{ product.averageRating }} out of 5, from {{ reviews|length }} reviews</p>
{% endif %}
```

### Stars without images

```twig
{% set rating = review.rating %}

<span aria-label="{{ rating }} out of 5">
  {% for i in 1..5 %}{{ i <= rating ? '&starf;'|raw : '&star;'|raw }}{% endfor %}
</span>
```

The `aria-label` matters. Without it a screen reader announces a row of star characters, which tells the listener nothing.

### A ratings breakdown

`getRatingCountInList()` omits ratings that nobody gave, so look each one up rather than looping over the result.

```twig
{% set breakdown = product.getRatingCountInList() %}
{% set total = breakdown|reduce((carry, row) => carry + row.total, 0) %}

{% for value in 5..1 %}
  {% set matching = breakdown|filter(row => row.rating == value) %}
  {% set count = matching|length ? matching|first.total : 0 %}

  <div>
    <span>{{ value }} stars</span>
    <progress value="{{ count }}" max="{{ total ?: 1 }}"></progress>
    <span>{{ count }}</span>
  </div>
{% endfor %}
```

### Showing which variant was bought

```twig
{% for review in product.getReviews() %}
  <p>
    {% for variant in review.variants %}
      {{ variant.title }}{{ not loop.last ? ', ' }}
    {% endfor %}
  </p>
{% endfor %}
```

### Showing who wrote it

A customer can be deleted while their review remains, so guard the reviewer.

```twig
{% set reviewer = review.reviewer %}

<p>{{ reviewer ? (reviewer.fullName ?: reviewer.friendlyName) : 'A customer' }}</p>
```

The same applies to `review.product` when you are looping over reviews from `craft.productReview.getReviews()` rather than from one product.

### Filtering and sorting

```twig
{# five star reviews only #}
{% set best = product.getReviews(5) %}

{# oldest first #}
{% set oldest = product.getReviews(null, 'dateCreated ASC') %}

{# highest rated first #}
{% set highest = product.getReviews(null, 'rating DESC') %}
```

### Sorting products by rating

```twig
{% set topRated = craft.products()
  .orderBy('averageRating DESC')
  .limit(10)
  .all() %}
```
