<?php

namespace aodihis\productreview\behaviors;

use aodihis\productreview\db\Table;
use craft\commerce\elements\db\ProductQuery;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use yii\base\Behavior;

class ProductQueryBehavior extends Behavior
{

    public function events(): array
    {
        return [
            ElementQuery::EVENT_AFTER_PREPARE => 'afterPrepare',
        ];
    }

    public function afterPrepare(): void
    {
        /** @var ProductQuery $productQuery */
        $productQuery = $this->owner;

        $reviewAverageQuery = (new Query())
            ->select([
                'Coalesce(CAST(AVG(rating) as decimal(10,2)),0) as averageRating',
                'productId'
            ])
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews'])
            ->groupBy(['reviews.productId']);

        $productQuery->subQuery->leftJoin(['reviews' => $reviewAverageQuery], '[[reviews.productId]] = [[commerce_products.id]]');
        $productQuery->subQuery->addSelect('reviews.averageRating as averageRating');
        $productQuery->query->addSelect('subquery.averageRating as averageRating');
    }
}