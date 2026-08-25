# Settings

## Finding the settings

In the control panel, go to **Settings**, then **Plugins**, then **Product Review**. If you have
`allowAdminChanges` disabled on that environment, the settings are read only, which is normal for
production.

You can also reach it directly at `/admin/settings/plugins/product-review`.

## Available settings

### Order Status to review

**Required. The plugin does nothing until this is set.**

The Commerce order status that causes reviews to be created. When an order moves into this status,
the plugin creates one review per product in that order.

Pick the status that means the customer has actually received the goods. Something like Completed or
Delivered works well. Choosing a status that happens at checkout, such as New, means customers are
asked to review things that have not arrived yet.

Notes worth knowing:

- Reviews are created on the **status change**, not retroactively. Orders already sitting in that
  status when you set this will not get reviews. Move an order out and back in to trigger it.
- Each order is only processed once. If an order returns to that status later, no duplicate reviews
  are created.
- One review is created per **product**, not per line item. A customer who buys three variants of
  the same shirt gets one review covering all three.

### Maximum days to review

How many days a customer has to submit a review, counted from when the review was created, which is
when the order reached the status above.

Set it to `0` to leave the window open forever.

Once the window closes, the review disappears from `getWaitingToReviewItems()` and the customer can
no longer submit it. The row stays in the database with a status of `expired`.

## Settings not exposed in the control panel

Two values are currently fixed in code and cannot be changed from the control panel.

| Value | Fixed at | Effect |
| --- | --- | --- |
| Maximum rating | `5` | Ratings are validated as 1 to 5 |
| Edits after submitting | none allowed | A review is final once submitted |

If you need a different maximum rating, that is a code change in
`src/models/Settings.php` rather than a setting. Making both configurable is planned.

## Setting values in config files

The settings are stored in project config, so they can be overridden per environment with a
`config/product-review.php` file:

```php
<?php

return [
    'orderStatusToReview' => 'completed',
    'maxDaysToReview' => 30,
];
```

Values in this file take precedence over the control panel, and the matching fields become read
only, which is Craft's normal behaviour for file-based plugin settings.
