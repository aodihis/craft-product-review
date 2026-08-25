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
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

class Reviews extends Component
{
    /**
     *  possible criteria param = [
     *          'status' => 'live' | 'pending' | 'expired' | null (default live;
     *                      null applies no filter, anything else throws)
     *          'id' => 'ID'
     *          'productId' => 'ID'
     *          'reviewerId' => 'ID'
     *          'rating' => int 1 to 5 ]
     * @param array $criteria
     * @param string $sort
     * @param int|null $limit
     * @param int $offset
     * @return array
     * @throws InvalidConfigException
     * @throws InvalidArgumentException if the status criterion is not recognised
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
        // Only submitted reviews. Every purchase creates an empty row up front, so without this
        // a product's review list includes placeholders the customer never filled in.
        $criteria = [
            'status' => ModelsReview::STATUS_LIVE,
            'productId' => $productId,
        ];
        if ($rating) {
            $criteria['rating'] = $rating;
        }
        return $this->getReviews($criteria, $sort);
    }

    public function getRatingCountInList(int $productId): array
    {
        $query = (new Query())
            ->select([
                'COUNT(id) as total',
                'rating'
            ])
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews'])
            ->where(['productId' => $productId])
            ->andWhere(['not', ['rating' => null]])
            ->orderBy('reviews.rating DESC')
            ->groupBy(['reviews.rating']);

        // Same restriction as getProductReviews(), via the shared definition of "live", so an
        // un-submitted review cannot show up as a rating bucket of its own.
        $this->_applyStatusCondition($query, ModelsReview::STATUS_LIVE);

        $reviewCount = $query->all();
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
        // Guest checkout still yields a customer: Commerce attaches an inactive user record so
        // the order has an owner. That account has no password and can never sign in, so a
        // review created for it could never be submitted — it would sit pending forever and
        // show up in the control panel as a review from a customer who does not really exist.
        $customer = $order->getCustomer();
        if (!$customer || !$customer->getIsCredentialed()) {
            return;
        }

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
            $reviews[$productId]->reviewerId = $customer->id;
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

    /**
     * Narrows a review query by status.
     *
     * `null` means "every status" — it is the only way to say that, since `status` is derived
     * rather than stored and a review is only ever pending, live or expired.
     *
     * Anything that is not a known status throws instead of being passed through to the query
     * as a column name: `status` is not a column, so an unrecognised value used to reach the
     * database as `WHERE status = '…'` and fail with "unknown column".
     *
     * @throws InvalidArgumentException if $status is not a recognised status
     */
    private function _applyStatusCondition(Query $query, ?string $status): void
    {
        if ($status === null) {
            return;
        }

        $maxDaysToReview = Plugin::getInstance()->getSettings()->maxDaysToReview;

        switch ($status) {
            case ModelsReview::STATUS_LIVE:
                $query->andWhere(['>', 'updateCount', 0]);
                break;

            case ModelsReview::STATUS_PENDING:
                $query->andWhere(['updateCount' => 0]);
                if ($maxDaysToReview) {
                    $query->andWhere(['>', 'reviews.dateCreated', $this->_reviewWindowCutoff($maxDaysToReview)]);
                }
                break;

            case ModelsReview::STATUS_EXPIRED:
                $query->andWhere(['updateCount' => 0]);
                if ($maxDaysToReview) {
                    $query->andWhere(['<=', 'reviews.dateCreated', $this->_reviewWindowCutoff($maxDaysToReview)]);
                } else {
                    // With no window configured nothing can expire, so match no rows.
                    $query->andWhere('1=0');
                }
                break;

            default:
                throw new InvalidArgumentException("Invalid review status: \"$status\"");
        }
    }

    /**
     * Returns the creation date at which the review window closes, ready for the database.
     *
     * The window condition `now < dateCreated + maxDays` is rearranged to
     * `dateCreated > now - maxDays` so the cutoff can be computed in PHP and bound as a
     * parameter. That avoids MySQL-only DATE_ADD()/INTERVAL, keeps the setting out of raw SQL,
     * and avoids depending on the database session's clock — NOW() follows the DB host's time
     * zone, while Craft stores dateCreated in UTC.
     */
    private function _reviewWindowCutoff(int $maxDaysToReview): string
    {
        return Db::prepareDateForDb((new DateTime('now'))->modify("- $maxDaysToReview day"));
    }

    private function _buildReviewQuery(array $criteria, string $sort = null, int $limit = null, int $offset = 0): Query
    {
        $query = $this->_buildQuery();

        foreach ($criteria as $key => $value) {
            if ($key === 'status') {
                $this->_applyStatusCondition($query, $value);
                continue;
            }

            if ($key === 'id') {
                $key = 'reviews.id';
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