<?php

namespace aodihis\productreview\services;

use aodihis\productreview\db\Table;
use aodihis\productreview\models\Review as ModelsReview;
use aodihis\productreview\Plugin;
use aodihis\productreview\records\Review;
use aodihis\productreview\records\ReviewVariant;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateTime;
use Exception;
use RuntimeException;
use yii\base\InvalidConfigException;

class Reviews extends Component
{
    /**
     *  possible criteria param = [
     *          'status' => 'live' | 'pending' | 'all' (default live)
     *          'productId' => 'ID'
     *          'reviewerId' => 'ID'
     *          'rating' => int 1 to 5 ]
     * @param array $criteria
     * @param string $sort
     * @param int|null $limit
     * @param int $offset
     * @return array
     * @throws InvalidConfigException
     */
    public function getReviews(array $criteria = [], string $sort = 'dateCreated DESC', int $limit = null, int $offset = 0): array
    {
        $query = $this->_buildReviewQuery($criteria, $sort, $limit, $offset);
        return $this->_buildReviewModels($query->all());
    }

    /**
     * @throws InvalidConfigException
     */
    public function getReviewById(int $id, ?string $status = ModelsReview::STATUS_LIVE): ?ModelsReview
    {
        $criteria = ['id' => $id, 'status' => $status];
        $query = $this->_buildReviewQuery($criteria);
        $record = $query->one();
        if (!$record) {
            return null;
        }
        return $this->_buildReviewModels([$record])[0];
    }

    public function getTotalReviews(array $criteria): int
    {
        $query = $this->_buildReviewQuery($criteria);
        return $query->count();
    }


    /**
     * @returns ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getProductReviews(int $productId, int $rating = null, string $sort = 'dateCreated DESC'): array
    {
        $criteria = ['productId' => $productId];
        if ($rating) {
            $criteria['rating'] = $rating;
        }
        return $this->getReviews($criteria, $sort);
    }

    public function getRatingCountInList(int $productId): array
    {
        $reviewCount = (new Query())
            ->select([
                'COUNT(id) as total',
                'rating'
            ])
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews'])
            ->where(['productId' => $productId])
            ->orderBy('reviews.rating DESC')
            ->groupBy(['reviews.rating'])->all();
        return array_map(static function ($rows){
            return [
                'total' => $rows['total'],
                'rating' => $rows['rating']
            ];
        }, $reviewCount);
    }

    /**
     * @returns ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getReviewHistoryForUser(int $reviewerId, string $sort = 'dateCreated DESC'): array
    {
        $criteria = ['status' =>  ModelsReview::STATUS_LIVE, 'reviewerId' => $reviewerId];
        return $this->getReviews($criteria, $sort);
    }

    /**
     * @return ModelsReview[]
     * @throws InvalidConfigException
     */
    public function getItemToReviewForUser(int $userId): array
    {
        return $this->getReviews([
            'status' => ModelsReview::STATUS_PENDING,
            'reviewerId' => $userId
        ]);
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
     * @throws InvalidConfigException
     */
    public function createReviewForOrder(Order $order): void
    {
        if ($this->isOrderAlreadyReviewed($order->id)) {
            return;
        }

        $reviews = [];

        foreach ($order->lineItems as $lineItem) {
            if (!$lineItem->purchasable instanceof Variant) {
                continue;
            }
            $productId = $lineItem->purchasable->getOwnerId();
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
        // Variant IDs are loaded separately rather than aggregated here. GROUP_CONCAT() is
        // MySQL-only (PostgreSQL spells it STRING_AGG), and dropping the join also lets
        // count() run without wrapping the query in a subquery.
        return (new Query())
            ->select(['reviews.*'])
            ->orderBy('reviews.id')
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews']);
    }

    /**
     * Builds review models from raw rows, resolving every row's variant IDs in one query.
     *
     * @param array[] $records
     * @return ModelsReview[]
     * @throws InvalidConfigException
     */
    private function _buildReviewModels(array $records): array
    {
        $variantIds = $this->_variantIdsByReviewId(array_column($records, 'id'));

        return array_map(
            fn(array $record): ModelsReview => $this->_buildReviewModel(
                $record,
                $variantIds[(int)$record['id']] ?? []
            ),
            $records
        );
    }

    /**
     * Returns variant IDs for the given reviews, indexed by review ID.
     *
     * @param int[]|string[] $reviewIds
     * @return array<int, int[]>
     */
    private function _variantIdsByReviewId(array $reviewIds): array
    {
        if (!$reviewIds) {
            return [];
        }

        $rows = (new Query())
            ->select(['reviewId', 'variantId'])
            ->from([Table::PRODUCT_REVIEW_VARIANTS])
            ->where(['reviewId' => $reviewIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $variantIds = [];
        foreach ($rows as $row) {
            $variantIds[(int)$row['reviewId']][] = (int)$row['variantId'];
        }

        return $variantIds;
    }

    /**
     * @param int[] $variantIds
     * @throws InvalidConfigException
     */
    private function _buildReviewModel(array $record, array $variantIds): ModelsReview
    {
        $record['variantIds'] = $variantIds;
        $comment = $record['comment'];
        $review = Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $record]]);
        $review->comment = $comment;
        return $review;
    }

    private function _buildReviewQuery(array $criteria, string $sort = null, int $limit = null, int $offset = 0): Query
    {
        $query = $this->_buildQuery();
        $maxDaysToReview = Plugin::getInstance()->getSettings()->maxDaysToReview;
        $updateCountCriteria = ['>', 'updateCount', 0];
        foreach ($criteria as $key => $value) {
            if ($key === 'id') {
                $key = 'reviews.id';
            }
            if ($key === 'status') {
                if ($value === 'live') {
                    $query->andWhere($updateCountCriteria);
                    continue;
                }
                if ($value === 'pending') {
                    $query->andWhere(['updateCount' => 0]);
                    if ($maxDaysToReview) {
                        // `now < dateCreated + maxDays` rearranged to `dateCreated > now - maxDays`,
                        // so the cutoff is computed in PHP and bound as a parameter. That avoids
                        // MySQL-only DATE_ADD()/INTERVAL, avoids interpolating the setting into raw
                        // SQL, and avoids depending on the database session's clock — NOW() follows
                        // the DB host's time zone, while Craft stores dateCreated in UTC.
                        $cutoff = (new DateTime('now'))->modify("- $maxDaysToReview day");
                        $query->andWhere(['>', 'reviews.dateCreated', Db::prepareDateForDb($cutoff)]);
                    }
                    continue;
                }

                if ($value === null) {
                    continue;
                }
            }

            $query->andWhere([$key => $value]);
        }

        if ($limit) {
            $query->limit($limit);
        }

        if ($offset) {
            $query->offset($offset);
        }
        if ($sort) {
            $query->orderBy($sort);
        }
        return $query;
    }
}