# Available Functions

The following functions you can call in your twig.

`craft.productReview.getReviews(rating, status, sort, limit)`

Retrieve customer-submitted reviews from across the store.

* **rating (optional):** Filter reviews by rating value (1 to 5). Use null to show all ratings.
* **status (optional):** Filter reviews based on status. Available options: live, pending, expired, or null for all. Default live.
* **sort (optional):** Sort reviews, with the default being `dateUpdated DESC`.
* **limit (optional):** Limit the number of reviews shown. Default 10.

Example

```twig
{% set reviews = craft.productReview.getReviews(null, 'live', 'rating DESC', 10) %}
{% for review in reviews %}
    {{ review.rating }}
{% endfor %}
```

There is no product filter on this function. Use `product.getReviews()` when you want one product's reviews.

`craft.productReview.getReviewById(id, status)`

Retrieve a customer review based on ID.

* **id (required):** ID of the review.
* **status (optional):** Filter based on status. Available options: live, pending, expired, or null for all. Default live.

Returns `null` if there is no review with that ID, or if it does not match the status.

Example

```twig
{% set review = craft.productReview.getReviewById(1) %}

{# pass null to fetch a review that has not been submitted yet #}
{% set review = craft.productReview.getReviewById(1, null) %}
```

{% hint style="info" %}
An unrecognised status throws an error rather than silently returning nothing.
{% endhint %}
