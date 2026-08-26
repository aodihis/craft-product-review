<?php

namespace aodihis\productreview;

use aodihis\productreview\behaviors\ProductBehavior;
use aodihis\productreview\behaviors\ProductQueryBehavior;
use aodihis\productreview\behaviors\UserBehavior;
use aodihis\productreview\fieldlayoutelements\BaseReviewsUiElement;
use aodihis\productreview\models\Settings;
use aodihis\productreview\plugin\Services;
use aodihis\productreview\services\FrontEnd;
use Craft;
use craft\base\Element;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\services\OrderHistories;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\models\FieldLayout;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event as YiiEvent;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Product Review plugin
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

    /**
     * Permission required to read reviews in the control panel.
     */
    public const PERMISSION_VIEW_REVIEWS = 'productReview-viewReviews';

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public function init(): void
    {
        parent::init();
        $this->_registerComponents();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });
    }

    /**
     * @throws InvalidConfigException
     */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
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
        $this->registerProductBehavior();
        $this->registerOnOrderStatusChange();
        $this->registerCpRules();
        $this->registerCraftVariable();
        $this->registerPermissions();
        $this->registerFieldLayoutUiElements();

    }

    /**
     * Offers the review list UI elements to the field layouts that can resolve reviews.
     *
     * Only product, order and user layouts get one, and each gets the single element written for
     * it, because the lookup differs: a product's reviews are the ones written *about* it, a
     * user's are the ones written *by* them, and an order's are the ones it asked for. Every other
     * layout, variants included, is left alone rather than offered a panel that could not resolve
     * anything.
     *
     * These are UI elements rather than fields on purpose. Reviews are written by customers on the
     * front end, so there is nothing to fill in and nothing to store on the element being edited,
     * and leaving it as a layout element keeps the choice of whether to show it with the developer.
     */
    private function registerFieldLayoutUiElements(): void
    {
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_UI_ELEMENTS,
            static function (DefineFieldLayoutElementsEvent $event) {
                /** @var FieldLayout $layout */
                $layout = $event->sender;

                // The element each UI element belongs to is declared on the class itself, so this
                // reads the same map the paging endpoint resolves against rather than keeping a
                // second copy that could drift from it.
                foreach (BaseReviewsUiElement::SOURCES as $class) {
                    if ($layout->type === $class::elementType()) {
                        $event->elements[] = $class;
                        return;
                    }
                }
            }
        );
    }

    private function registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            static function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('product-review', 'Product Review'),
                    'permissions' => [
                        self::PERMISSION_VIEW_REVIEWS => [
                            'label' => Craft::t('product-review', 'View product reviews'),
                        ],
                    ],
                ];
            }
        );
    }

    private function registerCraftVariable(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(YiiEvent $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;

                // Attach a service:
                $variable->set('productReview', FrontEnd::class);
            }
        );
    }

    private function registerCpRules(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function (RegisterUrlRulesEvent $event) {
                $event->rules['product-review'] = 'product-review/review-cp/index';
                $event->rules['product-review/index'] = 'product-review/review-cp/index';
                $event->rules['product-review/review/<id:\d+>'] = 'product-review/review-cp/view';

                // ...
            }
        );
    }

    private function registerUserBehavior(): void
    {
        Event::on(
            User::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            static function (DefineBehaviorsEvent $event) {
                $event->behaviors['product-review:user'] = UserBehavior::class;

            }
        );
    }

    private function registerProductBehavior(): void
    {
        Event::on(
            Product::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->behaviors['product-review:product'] = ProductBehavior::class;
            }
        );

        Event::on(
            ProductQuery::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event) {
                $event->behaviors['product-review:product-query'] = ProductQueryBehavior::class;
            }
        );

        Event::on(
            Product::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function (RegisterElementTableAttributesEvent $event) {
                $event->tableAttributes['averageRating'] = ['label' => Craft::t('product-review', 'Rating')];
            }
        );

        Event::on(
            Product::class,
            Element::EVENT_REGISTER_SORT_OPTIONS,
            function (RegisterElementSortOptionsEvent $event) {
                $event->sortOptions['averageRating'] = Craft::t('product-review', 'Rating');
            }
        );

    }

    private function registerOnOrderStatusChange(): void
    {

        Event::on(
            OrderHistories::class,
            OrderHistories::EVENT_ORDER_STATUS_CHANGE,
            function (OrderStatusEvent $event) {
                // @var OrderHistory $orderHistory
                $orderHistory = $event->orderHistory;
                /** @var Order $order */
                $order = $event->order;

                if ($orderHistory->getNewStatus()->handle === $this->getSettings()->orderStatusToReview) {
                    $this->getReviews()->createReviewForOrder($order);
                }
            }
        );
    }
}
