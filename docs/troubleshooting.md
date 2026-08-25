# Troubleshooting

## No reviews are being created

Work through these in order.

**Is an order status configured?** Settings, then Plugins, then Product Review. Without a status the
plugin does nothing at all, and this is by far the most common cause.

**Did the order actually change status?** Reviews are created on the change into that status, not on
orders already sitting in it. Move a test order out of the status and back in.

**Was the order already processed?** Each order only produces reviews once. Check for existing rows:

```sql
SELECT * FROM prorev_reviews WHERE orderId = 123;
```

**Does the order contain products with variants?** Only line items whose purchasable is a Commerce
variant produce reviews. Donations, custom line items, and other purchasable types are skipped.

## The customer sees nothing to review

**Have they signed in as the right account?** Reviews belong to the order's customer. An order placed
as a guest belongs to the account Commerce created for that email address, which the customer only
gains access to by registering with the same address.

**Has the review window passed?** Once `maxDaysToReview` elapses, the review is `expired` and drops
out of `getWaitingToReviewItems()`. Check:

```sql
SELECT id, dateCreated, rating FROM prorev_reviews WHERE reviewerId = 42;
```

A row with a `NULL` rating and an old `dateCreated` has expired. Set the setting to `0` to disable
the window.

**Have they already submitted it?** Submitted reviews move to `getReviewHistory()`.

## Submitting the form does nothing, or errors

**"You are not permitted to update this review"** means the signed-in customer does not own that
review. Check the `id` in the form matches a review from `getWaitingToReviewItems()` for the current
user.

**"This review has already been edited the maximum number of times"** means it has already been
submitted. Reviews are final by default.

**"This item can no longer be reviewed"** means the review window has closed.

**A 404** means there is no review with that ID at all.

**Nothing happens and no error shows** usually means the flash messages are not being output. Add:

```twig
{% set error = craft.app.session.getFlash('error') %}
{% if error %}<p>{{ error }}</p>{% endif %}
```

## Reviews show as blank on the product page

Check whether they were actually submitted. A review with a `NULL` rating has been created but never
filled in, and `product.getReviews()` correctly excludes those. If you are using
`craft.productReview.getReviews()` with a status of `null`, you will get un-submitted rows back, and
those have nothing to display.

## Comments show HTML tags as text

You are printing `review.comment` directly. Twig escapes it, so the markup appears as text. Use
`review.renderComment()` instead, which sanitizes and returns markup Twig will not escape.

Do not switch to `review.comment|raw`, which would output whatever the customer typed, including
scripts.

## The average rating is always zero

`averageRating` counts submitted reviews only. A product whose reviews are all still pending has an
average of `0`.

It is added by a behavior on the product query, so it is available on products fetched through
`craft.products()`. It is not available on a product built by hand in PHP without going through a
query.

## A control panel user cannot see the section

They need the **View product reviews** permission, at Settings, then Users, then the group, then
Permissions. This applies to anyone who could see the section before the permission existed, since
access is no longer implicit.

Admins hold every permission and are unaffected.

## The reviewer filter returns nothing

The filter needs at least two characters before it searches. It also uses Craft's search index, so a
newly created user may not be findable until that index has been updated:

```bash
./craft resave/users --update-search-index
```

The same applies to the product filter:

```bash
./craft resave/products --update-search-index
```

## Deleted products or customers cause errors

`review.product` and `review.reviewer` both return `null` once the element is deleted, and stay that
way until Craft's garbage collection removes the review row. Guard them:

```twig
{{ review.product ? review.product.title : 'Removed product' }}
```

## Checking the data directly

```sql
-- everything for one order
SELECT * FROM prorev_reviews WHERE orderId = 123;

-- submitted reviews for one product
SELECT * FROM prorev_reviews WHERE productId = 42 AND rating IS NOT NULL;

-- created but never submitted
SELECT * FROM prorev_reviews WHERE rating IS NULL;

-- which variants a review covers
SELECT * FROM prorev_reviews_variants WHERE reviewId = 7;
```

## Getting help

Report issues at https://github.com/aodihis/craft-product-review/issues, including your Craft and
Commerce versions, the plugin settings, and what you expected against what happened.
