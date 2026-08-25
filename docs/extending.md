# Events and extending

## Events fired by this plugin

**The plugin does not currently fire any events of its own.**

That is worth stating plainly rather than leaving you to search for them. There is no
`EVENT_BEFORE_SAVE_REVIEW`, no `EVENT_AFTER_CREATE_REVIEWS`, and no way to cancel a review being
created or saved through an event handler.

If you need one, the practical options today are the workarounds below. Adding proper events is a
reasonable feature request.

## Events the plugin listens to

Useful for understanding ordering, and for spotting conflicts with your own modules.

| Event | Class | What the plugin does |
| --- | --- | --- |
| `EVENT_ORDER_STATUS_CHANGE` | `commerce\services\OrderHistories` | Creates reviews when an order reaches the configured status |
| `EVENT_DEFINE_BEHAVIORS` | `craft\base\Model` | Adds the review methods to products and users |
| `EVENT_AFTER_PREPARE` | `craft\elements\db\ElementQuery` | Joins average ratings into every product query |
| `EVENT_REGISTER_TABLE_ATTRIBUTES` | `craft\base\Element` | Adds the Rating column to the products list |
| `EVENT_REGISTER_SORT_OPTIONS` | `craft\base\Element` | Makes the products list sortable by rating |
| `EVENT_REGISTER_CP_URL_RULES` | `craft\web\UrlManager` | Registers the control panel routes |
| `EVENT_REGISTER_PERMISSIONS` | `craft\services\UserPermissions` | Registers the View product reviews permission |
| `EVENT_INIT` | `craft\web\twig\variables\CraftVariable` | Adds `craft.productReview` |

## Reacting to a review being created

Since the plugin has no event, listen to the same Commerce event it does. Your handler and the
plugin's both run, so check the order has reviews before acting.

```php
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\services\OrderHistories;
use yii\base\Event;

Event::on(
    OrderHistories::class,
    OrderHistories::EVENT_ORDER_STATUS_CHANGE,
    function(OrderStatusEvent $event) {
        $order = $event->order;

        $reviews = \aodihis\productreview\Plugin::getInstance()
            ->getReviews()
            ->getReviews(['orderId' => $order->id, 'status' => null]);

        if (!$reviews) {
            return;
        }

        // for example, queue a "please review your purchase" email
    }
);
```

Handler order is not guaranteed. If your handler runs before the plugin's, the reviews will not
exist yet, which is why the example checks rather than assuming.

## Reacting to a review being submitted

There is no event, so the options are:

1. Poll for recently updated reviews from a queue job or a cron task, using
   `getReviews(['status' => 'live'], 'dateUpdated DESC')`.
2. Submit through your own controller action, which does your work and then calls
   `Reviews::saveReview()`.

## Using the services from PHP

```php
use aodihis\productreview\Plugin;

$reviews = Plugin::getInstance()->getReviews();
```

See the [PHP API reference](php-api.md) for the full list of methods.

## Creating a review yourself

Reviews are normally created by the order status change, but you can create one directly. All four
of these fields are required.

```php
use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;

$review = new Review();
$review->productId = $product->id;
$review->orderId = $order->id;
$review->reviewerId = $user->id;
$review->variantIds = [$variant->id];
$review->updateCount = 0;

Plugin::getInstance()->getReviews()->saveReview($review);
```

Note that `saveReview()` sanitizes the comment before storing it, so anything you pass through it is
cleaned on the way in.

## Overriding the templates

The control panel templates live in `src/templates` inside the plugin. Craft does not support
overriding plugin control panel templates from a project, so changing them means forking the plugin.

Front-end output is entirely yours. The plugin ships no front-end templates, only the data.

## Changing the maximum rating

The maximum is fixed at 5 in `src/models/Settings.php`:

```php
public static int $defaultMaxRating = 5;
```

Because it is a public static property, a module can change it during initialisation:

```php
use aodihis\productreview\models\Settings;

Settings::$defaultMaxRating = 10;
```

Do this before any review is validated or rendered. Note that existing reviews keep their stored
ratings, so lowering the maximum leaves ratings above it in the database.
