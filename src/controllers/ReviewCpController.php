<?php

namespace aodihis\productreview\controllers;


use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use Craft;
use craft\helpers\AdminTable;
use craft\web\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ReviewCpController extends Controller
{
    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Every action here reads review data, including the two search endpoints, which
        // otherwise expose user and product records to anyone who can reach the control panel.
        $this->requirePermission(Plugin::PERMISSION_VIEW_REVIEWS);

        return true;
    }

    public function actionIndex(): Response
    {
        $maxRating = Plugin::getInstance()->getSettings()->getMaxRating();
        return $this->renderTemplate('product-review/index', compact('maxRating'));
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionView(int $id): Response
    {
        $review = Plugin::getInstance()->getReviews()->getReviewById($id);
        return $this->renderTemplate('product-review/_view', compact('review'));
    }

    /**
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionGetTableData(): Response
    {
        $this->requireAcceptsJson();

        $limit = 10;
        $currentPage = $this->request->getParam('page', 1);
        $offset = ($currentPage - 1) * $limit;

        $filterProductId = (int)$this->request->getParam('productId') ?: null;
        $filterReviewerId = (int)$this->request->getParam('reviewerId') ?: null;
        $filterRating = $this->request->getParam('rating') ?: null;
        $criteria = ['status' => 'live'];
        if ($filterProductId) {
            $criteria['productId'] = $filterProductId;
        }
        if ($filterReviewerId) {
            $criteria['reviewerId'] = $filterReviewerId;
        }
        if ($filterRating) {
            $criteria['rating'] = $filterRating;
        }
        /** @var Review[] $reviews */
        $reviews = Plugin::getInstance()->getReviews()->getReviews($criteria, 'dateCreated DESC', 10, $offset);
        $total = Plugin::getInstance()->getReviews()->getTotalReviews($criteria);

        $rows = [];
        foreach ($reviews as $review) {
            // Both can be null while the review row still exists: a soft-deleted user or product
            // stays in the trash until garbage collection, so the row's foreign key has not
            // cascaded yet but the element no longer resolves.
            $product = $review->getProduct();
            $reviewer = $review->getReviewer();

            $rows[] = [
                'id' => $review->id,
                'product' => [
                    'title' => $product?->title ?: Craft::t('product-review', 'Removed product'),
                    'cpEditUrl' => $product?->getCpEditUrl() ?: '',
                ],
                'rating' => $review->rating,
                // Plain text, not the stored HTML: the column truncates and the table writes it
                // through innerHTML, so markup would either be escaped into visible tags or sliced
                // mid-element. The JS escapes this again before rendering.
                'comment' => $review->getPlainComment() ?: Craft::t('product-review', 'No feedback'),
                'reviewer' => [
                    // Guest customers have neither a username nor a name, so fall back to the
                    // email — otherwise their reviews render with a blank author.
                    'name' => $reviewer
                        ? ($reviewer->fullName ?: ($reviewer->username ?: $reviewer->email))
                        : Craft::t('product-review', 'Deleted user'),
                    'cpEditUrl' => $reviewer?->getCpEditUrl() ?: '',
                ],
                'url' => $review->getCpViewUrl(),
            ];
        }
        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($currentPage, $total, $limit),
            'data' => $rows
        ]);
    }
}