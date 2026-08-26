# Flow

The product review plugin only allows customers to review the purchased product, so here is the review flow.

1. Customers place an order on Craft Commerce.
2. Once the order is completed, Craft Commerce assigns it a status.
3. If the order status matches the `orderStatusToReview`, proceed to step 4. If not, update the order status manually or automatically to `orderStatusToReview`.
4. The plugin generates a list of purchased products eligible for review.
5. Customers can review these products.

One review is created per **product**, not per line item. A customer who buys three variants of the same shirt gets one review covering all three, and the variants they bought are recorded on it.

An order containing two different products produces two reviews.

### Statuses

A review's status is worked out from its data rather than set by hand.

| Status | Meaning |
| --- | --- |
| `pending` | Created and waiting for the customer, still inside the review window |
| `live` | The customer submitted it |
| `expired` | The review window closed before the customer submitted it |

Only `live` reviews should be shown publicly. `product.getReviews()` already filters to those, so you do not need to check the status yourself in a normal product template.

### The review window

`maxDaysToReview` is counted from the moment the review was created, which is when the order reached your configured status. It is not counted from the order date, and not from when the customer signs in.

A review that passes its window becomes `expired`, drops out of `currentUser.getWaitingToReviewItems()`, and is refused if submitted. The review itself is not deleted.

Set `maxDaysToReview` to `0` to disable the window entirely.

{% hint style="info" %}
Reviews are created on the status change, not retroactively. Orders already sitting in that status when you set the option will not get reviews. Move an order out and back in to trigger it.
{% endhint %}
