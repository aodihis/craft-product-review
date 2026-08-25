# PHP API reference

## Getting the service

```php
use aodihis\productreview\Plugin;

$reviews = Plugin::getInstance()->getReviews();
```

## Reviews service

`aodihis\productreview\services\Reviews`

### `getReviews(array $criteria = [], string $sort = 'dateCreated DESC', ?int $limit = null, int $offset = 0): Review[]`

The general query method. Supported criteria keys:

| Key | Type | Notes |
| --- | --- | --- |
| `status` | string, null | `live`, `pending`, `expired`, or `null` for all. Anything else throws |
| `id` | int | |
| `productId` | int | |
| `reviewerId` | int | |
| `orderId` | int | |
| `rating` | int | |

With no `status` key at all, no status filter is applied, which means un-submitted reviews are
included. Pass `'status' => 'live'` when the result is going somewhere public.

```php
$reviews = $service->getReviews([
    'status' => 'live',
    'productId' => 42,
], 'dateUpdated DESC', 20);
```

### `getReviewById(int $id, ?string $status = 'live'): ?Review`

Returns `null` if no review matches. Pass `null` as the status to fetch regardless of status.

### `getTotalReviews(array $criteria): int`

Counts matching reviews without loading them. Takes the same criteria as `getReviews()`.

### `getProductReviews(int $productId, ?int $rating = null, string $sort = 'dateCreated DESC'): Review[]`

Submitted reviews for one product. This is what `product.getReviews()` calls.

### `getRatingCountInList(int $productId): array`

How many submitted reviews gave each rating. Returns an array of arrays with `rating` and `total`
keys. Ratings with no reviews are absent from the result.

### `getReviewHistoryForUser(int $reviewerId, string $sort = 'dateCreated DESC'): Review[]`

Submitted reviews belonging to one customer.

### `getItemToReviewForUser(int $userId): Review[]`

Reviews that customer still needs to submit, excluding expired ones.

### `saveReview(Review $model, bool $runValidation = true): bool`

Saves a review. Returns `false` if validation fails, in which case the errors are on the model.
Sanitizes the comment before storing it.

Pass `false` for `$runValidation` only when creating an empty review, since an unsubmitted review has
no rating and would fail the rating rule.

### `isOrderAlreadyReviewed(int $orderId): bool`

Whether reviews have already been created for that order. Used to keep repeated status changes from
creating duplicates.

### `createReviewForOrder(Order $order): void`

Creates one review per product on the order. Does nothing if the order has already been processed,
or if it has no customer at all.

### `sanitizeComment(?string $comment): ?string`

Runs a comment through HTML Purifier with the same configuration as Twig's `|purify` filter. Called
automatically by `saveReview()`.

## FrontEnd service

`aodihis\productreview\services\FrontEnd`

Bound to `craft.productReview` in Twig. Thin wrapper over the Reviews service, with argument orders
suited to templates.

### `getReviews(?int $rating = null, ?string $status = 'live', ?string $sort = 'dateUpdated DESC', int $limit = 10): Review[]`

### `getReviewById(int $id, ?string $status = 'live'): ?Review`

## Review model

`aodihis\productreview\models\Review`

### Properties

| Property | Type |
| --- | --- |
| `$id` | `?int` |
| `$productId` | `?int` |
| `$orderId` | `?int` |
| `$reviewerId` | `?int` |
| `$variantIds` | `?int[]` |
| `$rating` | `?int` |
| `$comment` | `?string` |
| `$dateCreated` | `?DateTime` |
| `$dateUpdated` | `?DateTime` |

### Methods

| Method | Returns | Notes |
| --- | --- | --- |
| `getProduct()` | `?Product` | `null` if deleted |
| `getReviewer()` | `?User` | `null` if deleted |
| `getVariants()` | `Variant[]` | |
| `getStatus()` | `string` | `pending`, `live` or `expired` |
| `getIsPastReviewWindow()` | `bool` | |
| `renderComment()` | `?Markup` | Sanitized HTML, safe to output directly |
| `getPlainComment()` | `?string` | Tags stripped and entities decoded, for non-HTML output |
| `getCpViewUrl()` | `string` | |
