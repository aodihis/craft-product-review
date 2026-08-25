# The review object

Every method that returns a review hands back the same object, whether you reached it from Twig or
from PHP. The properties and methods below work the same way in both.

```twig
{{ review.rating }}
{{ review.renderComment() }}
```

```php
$review->rating;
$review->renderComment();
```

Twig also accepts the property form for anything with a getter, so `review.product` and
`review.status` work in templates, where PHP uses `$review->getProduct()` and `$review->getStatus()`.

## Values

| Property | Type | Notes |
| --- | --- | --- |
| `id` | int | |
| `rating` | int, null | `null` until submitted |
| `comment` | string, null | The raw stored value. See [Printing the comment](#printing-the-comment) before using it |
| `productId` | int | |
| `orderId` | int | |
| `reviewerId` | int | |
| `dateCreated` | DateTime | When the review was created, which starts the review window |
| `dateUpdated` | DateTime | |

## Related objects

| Property | Returns | Notes |
| --- | --- | --- |
| `product` | Product, null | `null` if the product was deleted |
| `reviewer` | User, null | `null` if the customer was deleted |
| `variants` | array of Variants | The specific variants bought |

## State

| Property | Type | Notes |
| --- | --- | --- |
| `status` | string | `pending`, `live`, or `expired` |
| `isPastReviewWindow` | bool | Whether the review window has closed |

## Printing the comment

The comment is written by a customer, so it must never be printed with `|raw`. Use one of these two
methods instead, depending on where it is going.

**`renderComment()`** for HTML output. It sanitizes the comment and returns markup that Twig will not
escape, so no `|raw` is needed.

```twig
<div>{{ review.renderComment() }}</div>
```

**`plainComment`** for anywhere that is not HTML, such as a CSV export, an email subject, or a meta
tag. It strips the tags and decodes the entities.

```twig
<meta name="description" content="{{ review.plainComment }}">
```

Both return `null` when there is no comment, so a fallback is easy:

```twig
{{ review.renderComment() ?? 'No feedback given' }}
```

Do not pair `plainComment` with `|raw`. It returns decoded text, so `|raw` would reintroduce exactly
the problem the sanitizing exists to prevent. Twig escapes it correctly on its own.
