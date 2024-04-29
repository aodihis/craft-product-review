<?php 

namespace aodihis\productreview\records;

use aodihis\productreview\db\Table;
use craft\db\ActiveRecord;

/**
 * 
 * @property int $id ID
 * @property int $orderId ORDER ID
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 * 
 */
class ReviewedOrder extends ActiveRecord
{

    public static function tableName(): string
    {
        return Table::PRODUCT_REVIEWED_ORDERS;
    }
}