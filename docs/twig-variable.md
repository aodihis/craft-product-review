# The craft.productReview Twig variable

Unlike the [product and user methods](product-and-user-methods.md), this one is Twig only. It exists
for fetching reviews outside the context of a single product or customer. In PHP, use the
[services](php-api.md) instead, which is what this variable calls.

## `craft.productReview.getReviews(rating, status, sort, limit)`

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

## `craft.productReview.getReviewById(id, status)`

```twig
{% set review = craft.productReview.getReviewById(42) %}
```

Returns `null` if there is no review with that ID, or if it does not match the status. The status
defaults to `'live'`, so pass `null` as the second argument to fetch one regardless of status.
