# Product and user methods

The plugin adds these methods to Commerce products and to Craft users. They are element behaviors,
which means the same call works **in Twig and in PHP**, on any product or user object you already
have. Nothing needs to be imported or injected.

```twig
{% for review in product.getReviews() %}
```

```php
foreach ($product->getReviews() as $review) {
```

Both reach the same method. The examples below show the Twig form, with the PHP equivalent beside it
where the two differ by more than syntax.

## On a product

### `getReviews(rating, sort)`

Returns the submitted reviews for that product. Un-submitted and expired ones are excluded, so this
is safe to loop over directly on a public page.

```twig
{% for review in product.getReviews() %}
  {{ review.rating }} out of 5
{% endfor %}
```

| Argument | Type | Default | Notes |
| --- | --- | --- | --- |
| `rating` | int, null | `null` | Only reviews with this rating |
| `sort` | string | `'dateCreated DESC'` | `dateCreated`, `dateUpdated` or `rating`, with `ASC` or `DESC` |

```twig
{# only five star reviews, oldest first #}
{% set best = product.getReviews(5, 'dateCreated ASC') %}
```

```php
$best = $product->getReviews(5, 'dateCreated ASC');
```

### `getRatingCountInList()`

Returns how many submitted reviews gave each rating, useful for a star breakdown bar. Ratings with
no reviews are not included in the result, so handle missing keys.

```twig
{% set breakdown = product.getRatingCountInList() %}

{% for row in breakdown %}
  <p>{{ row.rating }} stars: {{ row.total }}</p>
{% endfor %}
```

Each row is an array with two keys, `rating` and `total`.

### `averageRating`

The mean rating of all submitted reviews, as a number with two decimal places. Products with no
reviews return `0`.

```twig
<p>Average: {{ product.averageRating }}</p>
```

Unlike the methods above, this is not a behavior method but a value selected by the product query
itself. That is what makes it possible to **sort** by it:

```twig
{% set topRated = craft.products().orderBy('averageRating DESC').limit(10).all() %}
```

```php
$topRated = Product::find()->orderBy('averageRating DESC')->limit(10)->all();
```

Because it comes from the query, it is available on products fetched through a query, and not on a
product object built by hand.

## On a user

### `getWaitingToReviewItems()`

Reviews the customer still needs to submit. Already-submitted and expired reviews are excluded, so
this is exactly the list to render a form for.

```twig
{% for review in currentUser.getWaitingToReviewItems() %}
  {{ review.product.title }}
{% endfor %}
```

### `getReviewHistory()`

Reviews the customer has already submitted.

```twig
{% for review in currentUser.getReviewHistory() %}
  {{ review.product.title }}: {{ review.rating }}
{% endfor %}
```

Both work on any user object, not only `currentUser`, so a staff-facing template or a PHP module can
pass another user:

```php
$history = $user->getReviewHistory();
```
