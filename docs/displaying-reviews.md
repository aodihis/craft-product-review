# Displaying reviews on a product page

## The minimum

```twig
<h2>Reviews</h2>

{% for review in product.getReviews() %}
  <article>
    <p>{{ review.rating }} out of 5</p>
    <p>{{ review.renderComment() ?? 'No feedback given' }}</p>
  </article>
{% else %}
  <p>No reviews yet.</p>
{% endfor %}
```

`product.getReviews()` returns only submitted reviews, so nothing needs filtering by status.

## Adding the average and a count

```twig
{% set reviews = product.getReviews() %}

{% if reviews|length %}
  <p>{{ product.averageRating }} out of 5, from {{ reviews|length }} reviews</p>
{% endif %}
```

## A star rating without images

Two characters and a loop, no assets required.

```twig
{% macro stars(rating, max) %}
  <span aria-label="{{ rating }} out of {{ max }}">
    {%- for i in 1..max -%}
      {{ i <= rating ? '★' : '☆' }}
    {%- endfor -%}
  </span>
{% endmacro %}

{% import _self as ui %}

{{ ui.stars(review.rating, 5) }}
```

The `aria-label` matters. Without it a screen reader announces a row of star characters, which tells
the user nothing.

## A ratings breakdown

`getRatingCountInList()` returns only the ratings that have reviews, so build the full range yourself
and look each one up.

```twig
{% set breakdown = product.getRatingCountInList() %}
{% set total = breakdown|reduce((carry, row) => carry + row.total, 0) %}

{% for value in 5..1 %}
  {% set matching = breakdown|filter(row => row.rating == value) %}
  {% set count = matching|length ? (matching|first).total : 0 %}

  <div>
    <span>{{ value }} stars</span>
    <progress value="{{ count }}" max="{{ total }}"></progress>
    <span>{{ count }}</span>
  </div>
{% endfor %}
```

## Showing which variant was bought

Useful for clothing, where "runs small" only makes sense next to a size.

```twig
{% if review.variants|length %}
  <p>
    Bought:
    {%- for variant in review.variants -%}
      {{ variant.title }}{{ not loop.last ? ', ' }}
    {%- endfor -%}
  </p>
{% endif %}
```

## Showing who wrote it

The reviewer can be `null` if the account was deleted, and guest customers have no username or name,
so fall back through the options.

```twig
{% if review.reviewer %}
  <p>{{ review.reviewer.fullName ?: (review.reviewer.username ?: 'A customer') }}</p>
{% else %}
  <p>A customer</p>
{% endif %}
```

Printing `review.reviewer.email` on a public page would publish a customer's email address. Use the
name, or a generic label.

## Filtering and sorting

```twig
{# only five star reviews #}
{% set best = product.getReviews(5) %}

{# oldest first #}
{% set oldest = product.getReviews(null, 'dateCreated ASC') %}

{# most recently edited first #}
{% set recent = product.getReviews(null, 'dateUpdated DESC') %}
```

## Paginating

`product.getReviews()` returns everything at once, so paginate in Twig with `slice`:

```twig
{% set perPage = 10 %}
{% set page = craft.app.request.getParam('page', 1) %}
{% set reviews = product.getReviews() %}
{% set totalPages = (reviews|length / perPage)|round(0, 'ceil') %}

{% for review in reviews|slice((page - 1) * perPage, perPage) %}
  ...
{% endfor %}

{% for p in 1..totalPages %}
  <a href="?page={{ p }}">{{ p }}</a>
{% endfor %}
```

For a store with a very large number of reviews per product, fetch a limited set through
`craft.productReview.getReviews()` instead, which takes a limit.

## Listing recent reviews across the store

For a homepage block or a testimonials section:

```twig
{% for review in craft.productReview.getReviews(null, 'live', 'dateUpdated DESC', 5) %}
  <blockquote>
    <p>{{ review.renderComment() }}</p>
    <footer>
      {{ review.rating }} out of 5
      {% if review.product %}for {{ review.product.title }}{% endif %}
    </footer>
  </blockquote>
{% endfor %}
```

## Sorting products by rating

`averageRating` is part of the product query, so it works in `orderBy`:

```twig
{% set topRated = craft.products()
  .orderBy('averageRating DESC')
  .limit(8)
  .all() %}
```

Products with no reviews have an average of `0` and sort to the bottom, which is usually what you
want. To exclude them entirely, check `product.getReviews()|length` in the loop.

## Structured data for search engines

Search engines can show star ratings in results if you mark the reviews up. Use `plainComment` here,
because JSON-LD is not HTML.

```twig
{% set reviews = product.getReviews() %}

{% if reviews|length %}
  {% set data = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    'name': product.title,
    'aggregateRating': {
      '@type': 'AggregateRating',
      'ratingValue': product.averageRating,
      'reviewCount': reviews|length
    }
  } %}

  <script type="application/ld+json">{{ data|json_encode|raw }}</script>
{% endif %}
```
