<?php 

namespace aodihis\productreview\behaviors;

use aodihis\productreview\Plugin;
use yii\base\Behavior;

class ProductBehavior extends Behavior
{

    public function getReview(int $rating = null, string $sort = 'dateCreated DESC'): array
    {
        $product = $this->owner;
        return Plugin::getInstance()->getRevews()->getProductReview($product->id, $rating, $sort);
    }

    public function getAverateRating(): float
    {
        $product = $this->owner;
        return Plugin::getInstance()->getRevews()->getAverateRating($product->id);
    }
}