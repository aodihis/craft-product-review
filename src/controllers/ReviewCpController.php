<?php

namespace aodihis\productreview\controllers;


use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use craft\commerce\elements\Product;
use craft\elements\User;
use craft\helpers\AdminTable;
use craft\web\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ReviewCpController extends Controller
{

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
            $userQuery->search(urldecode($query));
        }

        $items = $userQuery->collect()->map(function (User $user) {
            return $user->toArray();
        });


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
            $productQuery->search(urldecode($query));
        }

        $items = $productQuery->collect()->map(function (Product $product) {
            return $product->toArray();
        });


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
            $rows[] = [
                'id' => $review->id,
                'product' => [
                    'title' => $review?->product?->title ? $review->product->title : 'Removed Product',
                    'cpEditUrl' => $review?->product?->getCpEditUrl() ? $review?->product->getCpEditUrl() : '' ,
                ],
                'rating' => $review->rating,
                'comment' => $review->comment ?: 'No feedback',
                'reviewer' => [
                    'name' => $review->reviewer->fullName ?: $review->reviewer->username,
                    'cpEditUrl' => $review->reviewer->getCpEditUrl(),
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