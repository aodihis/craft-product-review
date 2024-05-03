<?php 

namespace aodihis\productreview\records;

use aodihis\productreview\db\Table;
use craft\commerce\records\Product;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * 
 * @property int $id ID
 * @property int $productId Product ID
 * @property int $orderId Order ID
 * @property int $userId User ID
 * @property int $updateCount Update Count
 * @property int $rating Rating
 * @property string $comment Comment
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
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
        return $this->hasOne(Product::class, ['id' => 'productId']);
    }

}