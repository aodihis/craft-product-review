<?php

namespace aodihis\productreview\records;

use aodihis\productreview\db\Table;
use craft\commerce\records\Product;
use craft\db\ActiveRecord;
use craft\records\User;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property int $id ID
 * @property int $productId Product ID
 * @property int $orderId Order ID
 * @property int $reviewerId User ID
 * @property int $updateCount Update Count
 * @property int $rating Rating
 * @property string $comment Comment
 *
 */
class Review extends ActiveRecord
{

    public static function tableName(): string
    {
        return Table::PRODUCT_REVIEW_REVIEWS;
    }

    public function getProduct(): ActiveQueryInterface
    {
        return self::hasOne(Product::class, ['id' => 'productId']);
    }

    public function getReviewer(): ActiveQueryInterface
    {
        return self::hasOne(User::class, ['id' => 'reviewerId']);
    }
}