<?php

namespace aodihis\productreview\behaviors;

use aodihis\productreview\Plugin;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\elements\db\ElementQuery;
use craft\events\RegisterElementTableAttributesEvent;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

class ProductBehavior extends Behavior
{

    public ?float $averageRating = 0;

    public function events(): array
    {
        return [
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES => 'registerTableAttributes',
        ];
    }

    public function registerTableAttributes(RegisterElementTableAttributesEvent  $events): void
    {
    }

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