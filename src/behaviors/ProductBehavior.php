<?php

namespace aodihis\productreview\behaviors;

use aodihis\productreview\Plugin;
use craft\commerce\elements\Product;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

class ProductBehavior extends Behavior
{

    private ?float $_averageRating = null;

    public function setAverageRating(float $rating)
    {
        $this->_averageRating = $rating;
    }
    /**
     * @throws InvalidConfigException
     */
    public function getReview(int $rating = null, string $sort = 'dateCreated DESC'): array
    {
        /** @var Product $product */
        $product = $this->owner;
        return Plugin::getInstance()->getReviews()->getProductReviews($product->id, $rating, $sort);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getAverageRating(): float
    {
        if ($this->_averageRating !== null) {
            return $this->_averageRating;
        }

        /** @var Product $product */
        $product = $this->owner;
        $this->_averageRating =  Plugin::getInstance()->getReviews()->getProductAverageRating($product->id);
        return $this->_averageRating;
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