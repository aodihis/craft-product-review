<?php 

namespace aodihis\craftcommercereview\records;

use aodihis\craftcommercereview\db\Table;
use craft\commerce\records\Variant;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * 
 * @property int $id ID
 * @property int $reviewId Review ID
 * @property int $lineItemId Line Item ID
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 * 
 */
class ReviewedLineItem extends ActiveRecord
{

    public static function tableName(): string
    {
        return Table::COMMERCE_REVIEW_LINE_ITEMS;
    }

    public function getVariant(): ActiveQueryInterface
    {
        return $this->hasOne(Variant::class, ['id' => 'variantId']);
    }

}