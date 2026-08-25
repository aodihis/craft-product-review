<?php

namespace aodihis\productreview\fieldlayoutelements;

use aodihis\productreview\models\Review;
use Craft;
use craft\base\ElementInterface;
use craft\commerce\elements\Product;

/**
 * Lists the reviews customers have left for a product, for the product's field layout.
 */
class ProductReviews extends BaseReviewsUiElement
{
    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Product::class;
    }

    /**
     * @inheritdoc
     *
     * Submitted reviews only. Every purchase creates an empty row up front, so without the status
     * the list would be padded with placeholders no customer has filled in, which say nothing
     * about the product.
     */
    protected function criteria(ElementInterface $element): array
    {
        return [
            'status' => Review::STATUS_LIVE,
            'productId' => $element->id,
        ];
    }

    /**
     * @inheritdoc
     *
     * No product column, since every row is about the product being edited, and no status column,
     * because the criteria above admit live reviews only.
     */
    protected function columns(): array
    {
        return ['reviewer', 'rating', 'comment', 'date'];
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        return Craft::t('product-review', 'Product Reviews');
    }

    /**
     * @inheritdoc
     */
    protected function emptyMessage(): string
    {
        return Craft::t('product-review', 'No one has reviewed this product yet.');
    }
}
