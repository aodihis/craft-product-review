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
use craft\helpers\HtmlPurifier;
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

        // Defence in depth. Comments are reviewer-supplied and reach templates the plugin does not
        // control, plus JSON responses, so store them already sanitized rather than trusting every
        // consumer to remember. This does NOT replace sanitizing on output: rows written before
        // this existed are still raw, and escaping is context-dependent anyway — the control panel
        // table needs HTML-escaping, not purified markup, because it truncates and writes through
        // innerHTML.
        $model->comment = $this->sanitizeComment($model->comment);

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
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Strips anything executable out of a reviewer-supplied comment.
     *
     * Uses the same HTML Purifier configuration as Twig's `|purify` filter, so sanitizing on save
     * and sanitizing on output agree, and running both is harmless.
     */
    public function sanitizeComment(?string $comment): ?string
    {
        if ($comment === null || trim($comment) === '') {
            return $comment;
        }

        return HtmlPurifier::process($comment);
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
        // Guest checkout still yields a customer: Commerce attaches an inactive user record so the
        // order has an owner. Reviews are created for those accounts on purpose. The customer
        // cannot sign in to submit one yet, but if they later register with the same email Craft
        // claims that existing inactive record rather than making a new one — see
        // UsersController::actionSaveUser() — so the reviews become theirs, exactly as their
        // earlier orders do.
        $customer = $order->getCustomer();
        if (!$customer) {
            // No customer at all: nothing to attribute the review to, and reviewerId is NOT NULL.
            return;
        }

        if ($this->isOrderAlreadyReviewed($order->id)) {
            return;
        }

        // Group the purchased variants by the product that owns them, so one review covers every
        // variant of a product bought in the same order.
        $variantIdsByProduct = [];

        foreach ($order->getLineItems() as $lineItem) {
            $purchasable = $lineItem->getPurchasable();

            // Orders can contain other purchasable types, such as donations, which have no product
            // to review.
            if (!$purchasable instanceof Variant) {
                continue;
            }

            $variantIdsByProduct[$purchasable->getOwnerId()][] = $lineItem->purchasableId;
        }

        foreach ($variantIdsByProduct as $productId => $variantIds) {
            $review = new ModelsReview();
            $review->productId = $productId;
            $review->orderId = $order->id;
            $review->reviewerId = $customer->id;
            $review->updateCount = 0;
            // The same variant can appear on more than one line item, which would otherwise write
            // a duplicate row for it.
            $review->variantIds = array_values(array_unique($variantIds));

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

        return Craft::createObject(ModelsReview::class, ['config' => ['attributes' => $record]]);
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
            // Every caller sorts on a column that repeats — dateCreated above all, since
            // createReviewForOrder() writes one row per product in a single pass, so an order's
            // reviews all share a timestamp to the second. A sort on a non-unique column is not a
            // total order, which leaves LIMIT/OFFSET free to return a tied row on two consecutive
            // pages and drop another entirely. The ID breaks the tie; appending it is a no-op for
            // a sort that is already unique.
            $query->addOrderBy('reviews.id DESC');
        }
        return $query;
    }
}