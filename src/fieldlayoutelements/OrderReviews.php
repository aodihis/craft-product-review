<?php

namespace aodihis\productreview\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\commerce\elements\Order;

/**
 * Lists the reviews an order asked for, for the order field layout.
 */
class OrderReviews extends BaseReviewsUiElement
{
    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Order::class;
    }

    /**
     * @inheritdoc
     *
     * Every status, hence the explicit `null`. An order creates one review per product bought, so
     * the pending and expired rows are the point here: they show what the customer was asked to
     * review and never did, which is exactly what someone looking at an order wants to know.
     */
    protected function criteria(ElementInterface $element): array
    {
        return [
            'status' => null,
            'orderId' => $element->id,
        ];
    }

    /**
     * @inheritdoc
     *
     * No reviewer column: every review from one order has the same reviewer, who is already named
     * on the order.
     */
    protected function columns(): array
    {
        return ['product', 'rating', 'comment', 'status', 'date'];
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        return Craft::t('product-review', 'Order Reviews');
    }

    /**
     * @inheritdoc
     */
    protected function emptyMessage(): string
    {
        return Craft::t('product-review', 'This order has not asked for any reviews.');
    }
}
