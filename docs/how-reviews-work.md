# How reviews work

Understanding the lifecycle makes the rest of the documentation easier to follow, and explains most
of the questions that come up ("why is this review not showing", "why can the customer not submit").

## The lifecycle

```
order reaches the configured status
            |
            v
   one review row created per product        status: pending
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

### Guest orders

Guest checkouts get reviews too. Commerce attaches an inactive customer account to a guest order, so
the review is created against that account. The customer cannot sign in to submit it yet, but if
they later register using the same email address, Craft reuses that same account, and the review
becomes theirs along with their past orders.

One consequence to be aware of: the review window runs from the order, not from registration. A
guest who signs up two months later may find the review already expired.

## The review window

`maxDaysToReview` is counted from the moment the review was created, which is when the order reached
your configured status. It is not counted from the order date, and not from when the customer signs
in.

A review that passes its window becomes `expired`, drops out of
`currentUser.getWaitingToReviewItems()`, and is refused if submitted. The row is not deleted.

Set `maxDaysToReview` to `0` to disable the window entirely.

## Editing

By default a review is final once submitted. The customer gets one submission and no edits.

You can check this in a template before showing an edit form:

```twig
{% if review.isEditable %}
  ...show the form...
{% else %}
  <p>This review can no longer be changed.</p>
{% endif %}
```

## What happens when products or customers are deleted

Craft soft deletes elements, so there is a window where a review exists but the thing it points at
does not resolve any more.

- `review.product` returns `null` for a deleted product
- `review.reviewer` returns `null` for a deleted customer

Guard both in your templates if you display them. Once Craft's garbage collection runs, the review
row is removed by the database foreign key.

```twig
{{ review.product ? review.product.title : 'Removed product' }}
```
