<?php

namespace aodihis\craftcommercereview\services;

use aodihis\craftcommercereview\models\Review as ModelsReview;
use aodihis\craftcommercereview\Plugin;
use aodihis\craftcommercereview\records\Review;
use aodihis\craftcommercereview\records\ReviewedLineItem;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\db\ActiveQuery;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use Exception;
use DateTime;

class Reviews extends Component
{

    /**
     * @returns ModelsReview[]
     */
    public function getReviews(int $productId = null, int $userId = null, int $rating = null, string $sort = 'dateCreated DESC', int $limit = null, int $offset = null): array
    {
        $query = $this->_buildQuery($productId, $userId, $rating, $sort, $limit, $offset);

        $reviews = $query->all();
        foreach ($reviews as &$review) {
            $review = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $review]]);
        }

        return $reviews;
    }


    public function getTotalReviews(int $productId = null, int $userId = null, int $rating = null, string $sort = 'dateCreated DESC', int $limit = null, int $offset = null): int
    {
        $query = $this->_buildQuery($productId, $userId, $rating, $sort, $limit, $offset);
        return $query->count();
    }


    /**
     * @returns ModelsReview[]
     */
    public function getProductReviews(int $productId, int $rating = null, string $sort = 'dateCreated DESC'): array
    {

        return $this->getReviews($productId, null, $rating, $sort);
    }

    public function getProductReviewCount(): array
    {

        return [];
    }

     /**
     * @returns ModelsReview[]
     */
    public function getReviewHistoryForUser(int $userId, string $sort = 'dateCreated DESC'): array
    {
        return $this->getReviews(null, $userId, null, $sort);
    }

    /**
     * @return ModelReview[]
     */
    public function getItemToReviewForUser(int $userId): array
    {
        $reviews = Review::find()->where(['userId' => $userId])->andWhere( ['updateCount' => 0])->all();

        foreach ($reviews as &$review) {
            $coupon = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $review]]);
        }

        return $reviews;
    }

    public function isReviewCanBeUpdated(Review $review):bool
    {
        $currentTime = new DateTime("now");
        $maxDaysToReview = Plugin::getInstance()->getSettings()->maxDaysToReview;
        $reviewDateCreated = $review->dateCreated;

        if (($maxDaysToReview !== 0) && ($reviewDateCreated->modify("+ {$maxDaysToReview} day") > $currentTime)){
            return false;
        }

        if ($review->updateCount > Plugin::getInstance()->getSettings()->maxReviewLimit) {
            return false;
        }

        return true;
    }

    public function saveReview(ModelsReview $model, $runValidation = true): bool
    {
        $$isNew = !$model->id;

        if ($isNew) {
            $record = new Review();
        } else {
            $record = Review::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce-review', 'No review exists with the ID “{id}”',
                    ['id' => $model->id]));
            }
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Review not saved due to validation error.', __METHOD__);

            return false;
        }

        $fields = [
            'productId',
            'userId',
            'updateCount',
            'rating',
            'content',
        ];
        foreach ($fields as $field) {
            $record->$field = $model->$field;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();


        try {
            $record->save(false);
            $model->id = $record->id;
            // Update datetime attributes
            $model->dateCreated = DateTimeHelper::toDateTime($record->dateCreated);
            $model->dateUpdated = DateTimeHelper::toDateTime($record->dateUpdated);

            if ($isNew) {
                foreach($model->lineItemIds as $lineItemId) {
                    $reviewLineItem = new ReviewedLineItem();
                    $reviewLineItem->reviewId = $model->id;
                    $reviewLineItem->lineItemId = $lineItemId;
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }
    
    public function createReviewForOrder(Order $order) : void 
    {
        $lineItemIds = array_map(fn($ln) => $ln->id, $order->lineItems);

        //check if the line items already added or not.
        $totalReviewedLineItemsInDb = ReviewedLineItem::find()->where(['lineItemId' => $lineItemIds])->count();

        if ($totalReviewedLineItemsInDb){
            return;
        }

        $reviews = [];

        foreach($order->lineItems as $lineItem) {
            if (isset($reviews[$lineItem->productId])) {
                $reviews[$lineItem->productId]->addLineItem = $lineItem;
                continue;
            }

            $reviews[$lineItem->productId] = new Review();
            $reviews[$lineItem->productId]->productId = $lineItem->productId;
            $reviews[$lineItem->productId]->userId = $order->customerId;
            $reviews[$lineItem->productId]->updateCount = 0;
            $reviews[$lineItem->productId]->addLineItem = $lineItem;
        }
        

        foreach($reviews as $review) {
            $this->saveReview($review, false);
        }
    }

    private function _buildQuery(int $productId = null, int $userId = null, int $rating = null, string $sort = 'dateCreated DESC', int $limit = null, int $offset = null): ActiveQuery
    {
        $query = Review::find()->andWhere(['not', ['updateCount' => 0]]);

        if ($productId) {
            $query->andWhere(['productId' => $productId]);
        }

        if ($userId) {
            $query->andWhere(['userId' => $userId]);
        }
        
        if ($rating) {
            $query->andWhere(['rating' => $rating]);
        } else {
            $query->andWhere(['not',['rating' => null]]);
        }
        
        if ($limit) {
            $query->limit($limit);
        }

        $query->offset(null)->orderBy($sort);

        return $query;
    }
}