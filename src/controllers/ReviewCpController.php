<?php

namespace aodihis\productreview\controllers;


use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use Craft;
use craft\commerce\elements\Product;
use craft\elements\User;
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
     * @throws BadRequestHttpException
     */
    public function actionUserSearch(): Response
    {
        $this->requireAcceptsJson();

        $query = $this->request->getQueryParam('query');

        $limit = 30;
        $users = [];

        if ($query === null) {
            return $this->asJson($users);
        }

        $userQuery = User::find()->limit($limit);

        if ($query) {
            // No urldecode(): Craft has already decoded query params, so decoding again would
            // corrupt terms containing % or +.
            $userQuery->search($query);
        }

        // Only what the dropdown renders. toArray() would ship every user attribute — email,
        // preferences, timestamps — to anyone who can reach this endpoint.
        $items = $userQuery->collect()->map(fn(User $user) => [
            'id' => $user->id,
            'label' => $user->fullName ?: ($user->username ?: $user->email),
        ])->all();

        return $this->asJson(data: compact('items'));
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionProductSearch(): Response
    {
        $this->requireAcceptsJson();

        $query = $this->request->getQueryParam('query');

        $limit = 30;
        $users = [];

        if ($query === null) {
            return $this->asJson($users);
        }

        $productQuery = Product::find()->limit($limit);

        if ($query) {
            $productQuery->search($query);
        }

        $items = $productQuery->collect()->map(fn(Product $product) => [
            'id' => $product->id,
            'label' => $product->title,
        ])->all();

        return $this->asJson(data: compact('items'));
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
                'comment' => $review->comment ?: Craft::t('product-review', 'No feedback'),
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