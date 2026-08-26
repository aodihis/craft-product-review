# Review

Here is the list of properties and methods available on the review object. They work the same way in Twig and in PHP.

**Property**

| Property | Description |
| --- | --- |
| `id` | ID of review. |
| `productId` | The reviewed product ID. |
| `orderId` | The related order ID of review. |
| `variantIds` | The variant IDs related to the review. |
| `reviewerId` | The customer user ID. |
| `rating` | `Int`. The rating value. `null` until the customer submits. |
| `comment` | `String`. The raw stored comment. See Printing the comment below before using it. |
| `status` | `String`. The status of review. (live, pending, expired) |
| `dateCreated` | `DateTime`. The review creation date, which is when the order moved to the desired status. This starts the review window. |
| `dateUpdated` | `DateTime`. The date that review was updated. |
| `isPastReviewWindow` | `Bool`. Whether the review window has closed. |

**Method**

| Method | Description |
| --- | --- |
| `getProduct()` | ProductElement. Get the reviewed Product. Returns `null` if the product was deleted. |
| `getReviewer()` | UserElement. Get the related user. Returns `null` if the customer was deleted. |
| `getVariants()` | List of VariantElement. Get the list of reviewed variants. |
| `renderComment()` | Markup. The comment, sanitized and safe to output directly. |
| `plainComment` | String. The comment with tags stripped and entities decoded, for output that is not HTML. |

### Printing the comment

The comment is written by a customer, so it must never be printed with `|raw`.

Use `renderComment()` for HTML. It sanitizes the comment and returns markup Twig will not escape, so no `|raw` is needed.

```twig
<div>{{ review.renderComment() }}</div>
```

Use `plainComment` anywhere that is not HTML, such as a meta tag or an email subject. It strips the tags and decodes the entities.

```twig
<meta name="description" content="{{ review.plainComment }}">
```

Both return `null` when there is no comment, so a fallback is easy.

```twig
{{ review.renderComment() ?? 'No feedback given' }}
```

{% hint style="warning" %}
Do not pair `plainComment` with `|raw`. It returns decoded text, so `|raw` would reintroduce exactly the problem the sanitizing prevents.
{% endhint %}
