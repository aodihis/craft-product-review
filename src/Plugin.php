<?php

namespace aodihis\productreview;

use aodihis\productreview\behaviors\ProductBehavior;
use aodihis\productreview\behaviors\UserBehavior;
use Craft;
use aodihis\productreview\models\Settings;
use aodihis\productreview\plugin\Services;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\services\OrderHistories;
use craft\commerce\models\OrderHistory;
use craft\commerce\elements\Order;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;


/**
 * Commerce Review plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @author aodihis <aodihis@gmail.com>
 * @copyright aodihis
 * @license https://craftcms.github.io/license/ Craft License
 */
class Plugin extends BasePlugin
{
    use Services;
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;


    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();
        $this->_registerComponents();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('product-review/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        $this->registerUserBehavior();
        $this->registerOnOrderStatusChange();
    }

    private function registerUserBehavior(): void
    {
        Event::on(
            User::class,
            User::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->behaviors['product-review:user'] = UserBehavior::class;
                $event->behaviors['product-review:product'] = ProductBehavior::class;
            }
        );
    }

    private function registerOnOrderStatusChange() : void {

        Event::on(
            OrderHistories::class,
            OrderHistories::EVENT_ORDER_STATUS_CHANGE,
            function(OrderStatusEvent $event) {
                // @var OrderHistory $orderHistory
                $orderHistory = $event->orderHistory;
                // @var Order $order
                $order = $event->order;
        
                // Let the delivery department know the order’s ready to be delivered
                // ...

                if ($orderHistory->getNewStatus()->handle === $this->getSettings()->reviewOnOrderStatus) {
                    $this->getReviews()->createReviewForOrder($order);
                }
            }
        );
    }
}
