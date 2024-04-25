<?php 

namespace aodihis\craftcommercereview\behaviors;

use aodihis\craftcommercereview\Plugin;
use yii\base\Behavior;

class ProductBehavior extends Behavior
{

    public function getReview(int $rating = null, string $sort = 'dateCreated DESC'): array
    {
        $product = $this->owner;
        return Plugin::getInstance()->getRevews()->getProductReview($product->id, $rating, $sort);
    }
}