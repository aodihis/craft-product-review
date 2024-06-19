<?php

namespace aodihis\productreview\services;

use aodihis\productreview\db\Table;
use aodihis\productreview\models\Review as ModelsReview;
use aodihis\productreview\records\Review;
use aodihis\productreview\records\ReviewVariant;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use Exception;
use RuntimeException;
use yii\base\InvalidConfigException;

class Reviews extends Component
{

    /**
     * @returns ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getReviews(int $productId = null, int $reviewerId = null, int $rating = null, string $sort = 'dateCreated DESC', int $limit = null, int $offset = null): array
    {
        $query = $this->_buildQuery();
        if ($productId) {
            $query->andWhere(['productId' => $productId]);
        }

        if ($reviewerId) {
            $query->andWhere(['reviewerId' => $reviewerId]);
        }

        if ($rating) {
            $query->andWhere(['rating' => $rating]);
        } else {
            $query->andWhere(['not', ['rating' => null]]);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $query->offset($offset)->orderBy($sort);

        $reviews = $query->all();
        foreach ($reviews as &$review) {
            $review['variantIds'] = array_map('intval', explode(',', $review['variantIds']));
            $review = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $review]]);
        }

        return $reviews;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getReviewById(int $id): ?ModelsReview
    {
        $query = $this->_buildQuery();
        $query->where(['reviews.id' => $id]);
        $record = $query->one();

        if (!$record) {
            return null;
        }
        $record['variantIds'] = array_map('intval', explode(',', $record['variantIds']));
        return Craft::createObject(['class' => ModelsReview::class, 'attributes' => $record]);
    }

    public function getTotalReviews(int $productId = null, int $reviewerId = null, int $rating = null, int $limit = null): int
    {
        $query = $this->_buildQuery();
        if ($productId) {
            $query->andWhere(['productId' => $productId]);
        }

        if ($reviewerId) {
            $query->andWhere(['reviewerId' => $reviewerId]);
        }

        if ($rating) {
            $query->andWhere(['rating' => $rating]);
        } else {
            $query->andWhere(['not', ['rating' => null]]);
        }

        if ($limit) {
            $query->limit($limit);
        }
        return $query->count();
    }


    /**
     * @returns ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getProductReviews(int $productId, int $rating = null, string $sort = 'dateCreated DESC'): array
    {

        return $this->getReviews($productId, null, $rating, $sort);
    }

    public function getProductAverageRating(int $productId): float
    {
        $reviewAverage = (new Query())
            ->select([
                'AVG(rating) as averateRating',
            ])
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews'])
            ->where(['productId' => $productId])
            ->groupBy(['reviews.productId'])->one();

        if (!$reviewAverage) {
            return 0;
        }

        return number_format((float)$reviewAverage['averateRating'], 2, '.', '');
    }

    /**
     * @returns ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getReviewHistoryForUser(int $userId, string $sort = 'dateCreated DESC'): array
    {
        return $this->getReviews(null, $userId, null, $sort);
    }

    /**
     * @return ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getItemToReviewForUser(int $userId): array
    {
        $query = $this->_buildQuery();
        $query->where(['reviewerId' => $userId])->andWhere(['updateCount' => 0]);
        $reviews = $query->all();
        foreach ($reviews as &$review) {
            $review['variantIds'] = array_map('intval', explode(',', $review['variantIds']));
            $review = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $review]]);
        }
        return $reviews;
    }


    /**
     * @throws \yii\db\Exception
     * @throws Exception
     */
    public function saveReview(ModelsReview $model, $runValidation = true): bool
    {
        $isNew = !$model->id;

        if ($isNew) {
            $record = new Review();
        } else {
            $record = Review::findOne($model->id);

            if (!$record) {
                throw new RuntimeException(Craft::t('product-review', 'No review exists with the ID “{id}”',
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
            'reviewerId',
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
                foreach ($model->variantIds as $variantId) {
                    $reviewVariant = new ReviewVariant();
                    $reviewVariant->reviewId = $model->id;
                    $reviewVariant->variantId = $variantId;
                    $reviewVariant->save(false);
                }
            }
            $transaction?->commit();
        } catch (Exception $e) {
            $transaction?->rollBack();
            throw $e;
        }

        return true;
    }

    public function isOrderAlreadyReviewed(int $orderId): bool
    {
        $totalCount = Review::find()->where(['orderId' => $orderId])->count();
        return $totalCount > 0;
    }

    /**
     * @throws \yii\db\Exception
     */
    public function createReviewForOrder(Order $order): void
    {
        if ($this->isOrderAlreadyReviewed($order->id)) {
            return;
        }

        $reviews = [];

        foreach ($order->lineItems as $lineItem) {
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
            $reviews[$productId]->orderId = $order->id;
            $reviews[$productId]->reviewerId = $order->customerId;
            $reviews[$productId]->updateCount = 0;
            $reviews[$productId]->variantIds[] = $lineItem->purchasableId;
        }

        foreach ($reviews as $review) {
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