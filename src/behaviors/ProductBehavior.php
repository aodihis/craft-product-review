<?php

namespace aodihis\productreview\behaviors;

use aodihis\productreview\Plugin;
use craft\commerce\elements\Product;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

class ProductBehavior extends Behavior
{

    public ?float $averageRating = 0;

    /**
     * @throws InvalidConfigException
     */
    public function getReviews(int $rating = null, string $sort = 'dateCreated DESC'): array
    {
        /** @var Product $product */
        $product = $this->owner;
        return Plugin::getInstance()->getReviews()->getProductReviews($product->id, $rating, $sort);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getRatingCountInList(): array
    {
        /** @var Product $product */
        $product = $this->owner;
        return Plugin::getInstance()->getReviews()->getRatingCountInList($product->id);
    }
}