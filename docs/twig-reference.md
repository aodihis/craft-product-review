# Twig reference

Everything the plugin makes available to templates.

## On a product

The plugin attaches these to every Commerce product.

### `product.getReviews(rating, sort)`

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

### `product.getRatingCountInList()`

Returns how many submitted reviews gave each rating, useful for a star breakdown bar. Ratings with
no reviews are not included in the result, so handle missing keys.

```twig
{% set breakdown = product.getRatingCountInList() %}

{% for row in breakdown %}
  <p>{{ row.rating }} stars: {{ row.total }}</p>
{% endfor %}
```

Each row is an array with two keys, `rating` and `total`.

### `product.averageRating`

The mean rating of all submitted reviews, as a number with two decimal places. Products with no
reviews return `0`.

```twig
<p>Average: {{ product.averageRating }}</p>
```

This is added to the product query itself, which means you can also **sort** by it:

```twig
{% set topRated = craft.products().orderBy('averageRating DESC').limit(10).all() %}
```

## On the current user

### `currentUser.getWaitingToReviewItems()`

Reviews the signed-in customer still needs to submit. Already-submitted and expired reviews are
excluded, so this is exactly the list to render a form for.

```twig
{% for review in currentUser.getWaitingToReviewItems() %}
  {{ review.product.title }}
{% endfor %}
```

### `currentUser.getReviewHistory()`

Reviews the customer has already submitted.

```twig
{% for review in currentUser.getReviewHistory() %}
  {{ review.product.title }}: {{ review.rating }}
{% endfor %}
```

Both work on any user object, not only `currentUser`, so an admin-facing template can pass another
user.

## The `craft.productReview` variable

For fetching reviews outside the context of a product or a user.

### `craft.productReview.getReviews(rating, status, sort, limit)`

```twig
{# the ten most recent reviews across the whole store #}
{% set latest = craft.productReview.getReviews(null, 'live', 'dateUpdated DESC', 10) %}
```

| Argument | Type | Default |
| --- | --- | --- |
| `rating` | int, null | `null` |
| `status` | string, null | `'live'` |
| `sort` | string, null | `'dateUpdated DESC'` |
| `limit` | int | `10` |

Valid statuses are `'live'`, `'pending'`, `'expired'`, and `null` for all of them. Any other value
throws an error rather than silently returning nothing.

There is no product filter on this method. Use `product.getReviews()` when you want one product's
reviews.

### `craft.productReview.getReviewById(id, status)`

```twig
{% set review = craft.productReview.getReviewById(42) %}
```

Returns `null` if there is no review with that ID, or if it does not match the status. The status
defaults to `'live'`, so pass `null` as the second argument to fetch one regardless of status.

## On a single review

Given a `review` from any of the methods above.

### Values

| Property | Type | Notes |
| --- | --- | --- |
| `review.id` | int | |
| `review.rating` | int, null | `null` until submitted |
| `review.comment` | string, null | The raw stored value. See the note below before printing it |
| `review.productId` | int | |
| `review.orderId` | int | |
| `review.reviewerId` | int | |
| `review.dateCreated` | DateTime | When the review was created, which starts the review window |
| `review.dateUpdated` | DateTime | |

### Related objects

| Property | Returns | Notes |
| --- | --- | --- |
| `review.product` | Product, null | `null` if the product was deleted |
| `review.reviewer` | User, null | `null` if the customer was deleted |
| `review.variants` | array of Variants | The specific variants bought |

### State

| Property | Type | Notes |
| --- | --- | --- |
| `review.status` | string | `pending`, `live`, or `expired` |
| `review.isPastReviewWindow` | bool | Whether the review window has closed |
| `review.cpViewUrl` | string | Link to the review in the control panel |

### Printing the comment

The comment is written by a customer, so it must never be printed with `|raw`. Use one of these two
methods instead, depending on where it is going.

**`review.renderComment()`** for HTML output. It sanitizes the comment and returns markup that Twig
will not escape, so no `|raw` is needed.

```twig
<div>{{ review.renderComment() }}</div>
```

**`review.plainComment`** for anywhere that is not HTML, such as a CSV export, an email subject, or
a meta tag. It strips the tags and decodes the entities.

```twig
<meta name="description" content="{{ review.plainComment }}">
```

Both return `null` when there is no comment, so a fallback is easy:

```twig
{{ review.renderComment() ?? 'No feedback given' }}
```

Do not pair `plainComment` with `|raw`. It returns decoded text, so `|raw` would reintroduce exactly
the problem the sanitizing exists to prevent. Twig escapes it correctly on its own.
