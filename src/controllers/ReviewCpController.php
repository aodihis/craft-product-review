<?php

namespace aodihis\productreview\controllers;


use aodihis\productreview\Plugin;
use craft\commerce\elements\Product;
use craft\elements\User;
use craft\helpers\AdminTable;
use craft\web\Controller;
use craft\web\Response;

class ReviewCpController extends Controller
{

    public function actionIndex(): Response
    {
        $maxRating = Plugin::getInstance()->getSettings()->getMaxRating();
        return $this->renderTemplate('product-review/index', compact('maxRating'));
    }

    public function actionView(int $id): Response
    {

        $review = Plugin::getInstance()->getReviews()->getReviewById($id);
        return $this->renderTemplate('product-review/_view', compact('review'));
    }

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


    public function actionGetTableData()
    {
        $this->requireAcceptsJson();

        $limit = 10;
        $currentPage = $this->request->getParam('page', 1);

        $filterProductId = (int)$this->request->getParam('productId') ?: null;
        $filterReviewerId = (int)$this->request->getParam('reviewerId') ?: null;
        $filterRating = $this->request->getParam('rating') ?: null;
        $reviews = Plugin::getInstance()->getReviews()->getReviews($filterProductId, $filterReviewerId, $filterRating);
        $total = Plugin::getInstance()->getReviews()->getTotalReviews($filterProductId, $filterReviewerId, $filterRating);

        $rows = [];
        foreach ($reviews as $review) {
            $rows[] = [
                'id' => $review->id,
                'product' => [
                    'title' => $review->product->title,
                    'cpEditUrl' => $review->product->getCpEditUrl(),
                ],
                'rating' => $review->rating,
                'comment' => $review->comment ?: 'No feedback',
                'reviewer' => [
                    'name' => $review->reviewer->fullName ?: $review->reviewer->username,
                    'cpEditUrl' => $review->reviewer->getCpEditUrl(),
                ],
                'url' => $review->getViewUrl(),
            ];
        }
        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($currentPage, $total, $limit),
            'data' => $rows
        ]);
    }
}