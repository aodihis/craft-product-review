<?php 

namespace aodihis\productreview\records;

use aodihis\productreview\db\Table;
use craft\commerce\records\Variant;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * 
 * @property int $id ID
 * @property int $orderId ORDER ID
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 * 
 */
class ReviewedOrders extends ActiveRecord
{

    public static function tableName(): string
    {
        return Table::PRODUCT_REVIEWED_ORDERS;
    }
}