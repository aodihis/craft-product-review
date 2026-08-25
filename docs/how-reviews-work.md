# How reviews work

Understanding the lifecycle makes the rest of the documentation easier to follow, and explains most
of the questions that come up ("why is this review not showing", "why can the customer not submit").

## The lifecycle

```
order reaches the configured status
            |
            v
   one review created per product        status: pending
   (rating and comment are empty)
            |
            +-- customer submits it  ------>  status: live
            |
            +-- review window passes  ----->  status: expired
                (never submitted)
```

## The three statuses

A review's status is worked out from its data rather than stored in a column.

| Status | Meaning | Condition |
| --- | --- | --- |
| `pending` | Created, waiting for the customer | Not submitted, still inside the review window |
| `live` | Submitted by the customer | Has been submitted |
| `expired` | Never submitted, window has closed | Not submitted, past `maxDaysToReview` |

Only `live` reviews should be shown publicly. `product.getReviews()` already filters to those, so
you do not need to check the status yourself in a normal product template.

## What gets created, and when

When an order moves into your configured status, the plugin looks at every line item, groups them by
product, and creates one review per product. The variants the customer bought are recorded against
the review, so you can show exactly which size or colour was purchased.

An order that contains two different products produces two reviews. An order containing three
variants of one product produces one review listing three variants.

## The review window

`maxDaysToReview` is counted from the moment the review was created, which is when the order reached
your configured status. It is not counted from the order date, and not from when the customer signs
in.

A review that passes its window becomes `expired`, drops out of
`currentUser.getWaitingToReviewItems()`, and is refused if submitted. The review itself is not deleted.

Set `maxDaysToReview` to `0` to disable the window entirely.
