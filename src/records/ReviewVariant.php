<?php

namespace aodihis\productreview\records;

use aodihis\productreview\db\Table;
use craft\commerce\records\Variant;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property int $id ID
 * @property int $reviewId Review ID
 * @property int $variantId Variant ID
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 *
 */
class ReviewVariant extends ActiveRecord
{

    public static function tableName(): string
    {
        return Table::PRODUCT_REVIEW_VARIANTS;
    }

    public function getVariant(): ActiveQueryInterface
    {
        return $this->hasOne(Variant::class, ['id' => 'variantId']);
    }

}