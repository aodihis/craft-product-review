# Configuration

Create a `product-review.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    'orderStatusToReview' => 'completed',
    'maxDaysToReview' => 30,
];
```

### Configuration options

* `orderStatusToReview` - The Commerce order status that makes purchased products reviewable. When an order moves into this status, the plugin creates one review per product in that order.
* `maxDaysToReview` - How many days the customer has to submit a review, counted from when the review was created. Set it to `0` to leave the window open forever.

### Choosing an order status

Pick the status that means the customer has actually received the goods. Something like Completed or Delivered works well. Choosing a status that happens at checkout, such as New, means customers are asked to review things that have not arrived yet.

Notes worth knowing:

* Reviews are created on the **status change**, not retroactively. Orders already sitting in that status when you set this will not get reviews. Move an order out and back in to trigger it.
* Each order is only processed once. If an order returns to that status later, no duplicate reviews are created.
* One review is created per **product**, not per line item. A customer who buys three variants of the same shirt gets one review covering all three.

### Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Product Review.

Values set in the config file take precedence over the Control Panel, and the matching fields become read only, which is Craft's normal behaviour for file-based plugin settings.

{% hint style="info" %}
`orderStatusToReview` must be set to ensure the flow works properly.
{% endhint %}
