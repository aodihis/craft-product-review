# Configuration

Create a `product-review.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    'orderStatusToReview' => 'completed',
    'maxDaysToReview' => 30,
    'maxCharactersPerReview' => 0,
    'ratingAlgorithm' => 'average',
    'bayesianPriorWeight' => 10,
];
```

### Configuration options

* `orderStatusToReview` - The Commerce order status that makes purchased products reviewable. When an order moves into this status, the plugin creates one review per product in that order.
* `maxDaysToReview` - How many days the customer has to submit a review, counted from when the review was created. Set it to `0` to leave the window open forever.
* `maxCharactersPerReview` - How many characters a customer can write in a review comment. Set it to `0` for no limit. The count is taken on what the customer typed, so it matches what they see in the field.
* `ratingAlgorithm` - How `product.averageRating` is worked out. Either `average` or `bayesian`. Leave it unset for `average`.
* `bayesianPriorWeight` - Only used when `ratingAlgorithm` is `bayesian`. How many reviews a product needs before its own rating outweighs the average across your whole catalogue. Set it to `0` to turn the adjustment off.

### Choosing an order status

Pick the status that means the customer has actually received the goods. Something like Completed or Delivered works well. Choosing a status that happens at checkout, such as New, means customers are asked to review things that have not arrived yet.

Notes worth knowing:

* Reviews are created on the **status change**, not retroactively. Orders already sitting in that status when you set this will not get reviews. Move an order out and back in to trigger it.
* Each order is only processed once. If an order returns to that status later, no duplicate reviews are created.
* One review is created per **product**, not per line item. A customer who buys three variants of the same shirt gets one review covering all three.

### Choosing a rating algorithm

`ratingAlgorithm` decides what `product.averageRating` returns, both where you print it and where you sort on it.

`average` is the plain mean of the submitted ratings, and is what you get if you set nothing. It is the honest number for a single product: three 5-star reviews average 5. Its weakness is ranking. A product with one 5-star review beats a product with two hundred reviews averaging 4.8, so a "top rated" list fills up with products that have barely been reviewed.

`bayesian` fixes that by pulling each product's mean towards the average across your whole catalogue. How hard it pulls depends on how many reviews the product has: a product with one review is moved a long way, and a product with hundreds is barely moved at all. Products then sort by how confident the ratings are, not just how high they are.

`bayesianPriorWeight` controls the strength. A product with that many reviews sits halfway between its own mean and the catalogue average. Raise it to be harsher on products with few reviews, lower it to trust small numbers of reviews sooner. The default of `10` suits most stores.

To make the difference concrete, on a store whose catalogue averages 3.7:

| Reviews | Plain average | Bayesian, weight 10 |
| --- | --- | --- |
| 200 reviews averaging 4.8 | 4.80 | 4.75 |
| 3 reviews averaging 4.67 | 4.67 | 3.94 |
| 1 review of 5 stars | 5.00 | 3.84 |
| 1 review of 1 star | 1.00 | 3.48 |
| 300 reviews averaging 3.0 | 3.00 | 3.02 |

Two things to weigh before switching:

* **The adjusted number is what customers see.** A product with a single 5-star review displays 3.84, not 5. If you print `averageRating` on a product page, choose `bayesian` only if you are happy showing that. You can also keep `average` and rank by something else in your own template.
* **Products with no ratings score `0` under both algorithms**, so they sort last either way.

Switching algorithms takes effect immediately and changes nothing that is stored, so you can move between them freely.

{% hint style="info" %}
The catalogue average behind `bayesian` is cached for an hour, and refreshed as soon as a review is submitted. If you change ratings directly in the database, expect up to an hour before it is reflected.
{% endhint %}

### Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Product Review.

Values set in the config file take precedence over the Control Panel, and the matching fields become read only, which is Craft's normal behaviour for file-based plugin settings.

{% hint style="info" %}
`orderStatusToReview` must be set to ensure the flow works properly.
{% endhint %}
