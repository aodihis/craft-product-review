# Available Custom Behavior

The following user and product methods you can call in your twig. They are added to the Craft user and Commerce product elements, so the same call also works from PHP.

```twig
{% set reviews = product.getReviews() %}
```

```php
$reviews = $product->getReviews();
```

### User

| Method | Description |
| --- | --- |
| `getWaitingToReviewItems()` | Get list of reviews this user still needs to submit. Expired ones are excluded, so this is the list to render a form for. |
| `getReviewHistory()` | Get list of reviews this user has already submitted. |

Both work on any user object, not only `currentUser`.

### Product

| Method | Description |
| --- | --- |
| `getReviews()` | Get the submitted reviews for this product. |
| `getRatingCountInList()` | Get list of rating count for this product. |
| `averageRating` | The mean rating of the submitted reviews. |

`getReviews()`

Arguments:

* rating: Optional, `int`. Default `null`.
* sort: Optional, string. By default `dateCreated DESC`.

Returns:

Array of Review Object. Un-submitted and expired reviews are excluded, so it is safe to loop over on a public page.

`getRatingCountInList()`

Returns:

Array of rating count. Ratings with no reviews are not included in the result, so handle missing values.

Example

```
[
    [ 'total' => 20, 'rating' => 5 ],
    [ 'total' => 27, 'rating' => 4 ],
    [ 'total' => 29, 'rating' => 3 ],
    [ 'total' => 1, 'rating' => 2 ],
]
```

`averageRating`

The mean rating of all submitted reviews, to two decimal places. Products with no reviews return `0`.

```twig
<p>Average: {{ product.averageRating }}</p>
```

This one comes from the product query rather than a method, which is what makes it possible to sort by it.

```twig
{% set topRated = craft.products().orderBy('averageRating DESC').limit(10).all() %}
```

{% hint style="info" %}
Because it comes from the query, `averageRating` is available on products fetched through a query, and not on a product object built by hand.
{% endhint %}
