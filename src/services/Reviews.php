<?php

namespace aodihis\productreview\services;

use aodihis\productreview\db\Table;
use aodihis\productreview\models\Review as ModelsReview;
use aodihis\productreview\Plugin;
use aodihis\productreview\records\Review;
use aodihis\productreview\records\ReviewedOrder;
use aodihis\productreview\records\ReviewVariant;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\db\ActiveQuery;
use craft\helpers\DateTimeHelper;
use Exception;
use DateTime;
use craft\db\Query;

class Reviews extends Component
{

    /**
     * @returns ModelsReview[]
     */
    public function getReviews(int $productId = null, int $userId = null, int $rating = null, string $sort = 'dateCreated DESC', int $limit = null, int $offset = null): array
    {
        $query = $this->_buildQuery($productId, $userId, $rating, $sort, $limit, $offset);
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

        $reviews = $query->all();
        foreach ($reviews as &$review) {
            $review['variantIds'] = array_map('intval', explode(',', $review['variantIds']));
            $review = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $review]]);
        }

        return $reviews;
    }

    public function getReviewById(int $id): ?ModelsReview
    {
        $query = $this->_buildQuery();
        $query->where(['reviews.id' => $id]);
        $record =  $query->one();

        if (!$record) {
            return null;
        }
        $model = new ModelsReview();
        $record['variantIds'] = array_map('intval', explode(',', $record['variantIds']));
        $model = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $record]]);
        return $model;
    }

    public function getTotalReviews(int $productId = null, int $userId = null, int $rating = null, string $sort = 'dateCreated DESC', int $limit = null, int $offset = null): int
    {
        $query = $this->_buildQuery($productId, $userId, $rating, $sort, $limit, $offset);
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
        $query = $this->_buildQuery();
        $query->where(['userId' => $userId])->andWhere( ['updateCount' => 0]);
        $reviews = $query->all();
        foreach ($reviews as &$review) {
            $review['variantIds'] = array_map('intval', explode(',', $review['variantIds']));
            $review = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $review]]);
        }
        return $reviews;
    }



    public function saveReview(ModelsReview $model, $runValidation = true): bool
    {
        $isNew = !$model->id;

        if ($isNew) {
            $record = new Review();
        } else {
            $record = Review::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('product-review', 'No review exists with the ID “{id}”',
                    ['id' => $model->id]));
            }
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Review not saved due to validation error.', __METHOD__);

            return false;
        }

        $fields = [
            'productId',
            'orderId',
            'userId',
            'updateCount',
            'rating',
            'comment',
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
                foreach($model->variantIds as $variantId) {
                    $reviewLineItem = new ReviewVariant();
                    $reviewLineItem->reviewId = $model->id;
                    $reviewLineItem->variantId = $variantId;
                    $reviewLineItem->save(false);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }
    
    public function isOrderAlreadyReviewed(int $orderId) : bool {
        $totalCount = Review::find()->where(['orderId' => $orderId])->count();
        return $totalCount > 0;
    }

    public function createReviewForOrder(Order $order) : void 
    {
        if ($this->isOrderAlreadyReviewed($order->id)) {
            return;
        }

        $reviews = [];

        foreach($order->lineItems as $lineItem) {
            $productId = $lineItem->purchasable->productId;
            if (!$lineItem->purchasable instanceof Variant) {
                continue;
            }
            if (isset($reviews[$productId])) {
                $reviews[$productId]->variantIds[] = $lineItem->purchasableId;
                continue;
            }

            $reviews[$productId] = new ModelsReview();
            $reviews[$productId]->productId = $productId;
            $reviews[$productId]->orderId = $order->id;;
            $reviews[$productId]->userId = $order->customerId;
            $reviews[$productId]->updateCount = 0;
            $reviews[$productId]->variantIds[] = $lineItem->purchasableId;
        }
        
        foreach($reviews as $review) {
            $this->saveReview($review, false);
        } 
    }

    private function _buildQuery(): Query
    {

        return (new Query())
            ->select([
                'reviews.*',
                'GROUP_CONCAT(`variantId` ORDER BY variants.id) as variantIds',
            ])
            ->orderBy('reviews.id')
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews'])
            ->leftJoin(Table::PRODUCT_REVIEW_VARIANTS . ' variants', '[[variants.reviewId]]=[[reviews.id]]')
            ->groupBy(['reviews.id']);
    }
}