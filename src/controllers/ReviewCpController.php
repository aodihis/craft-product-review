<?php 

namespace aodihis\productreview\controllers;


use aodihis\productreview\Plugin;
use Craft;
use craft\commerce\elements\Product;
use craft\elements\User;
use craft\web\Controller;
use craft\web\Response;
use yii\web\NotFoundHttpException;
use craft\helpers\AdminTable;
use craft\helpers\Html;

class ReviewCpController extends Controller
{

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

        $items = $userQuery->collect()->map(function(User $user) {
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

        $items = $productQuery->collect()->map(function(Product $product) {
            return $product->toArray();
        });


        return $this->asJson(data: compact('items'));
    }


    public function actionGetTableData()
    {
        $this->requireAcceptsJson();

        $limit = 10;
        $currentPage = $this->request->getParam('page', 1);
        

        $reviews = Plugin::getInstance()->getReviews()->getReviews();
        $total = Plugin::getInstance()->getReviews()->getTotalReviews();

        $rows = [];
        foreach ($reviews as $review) {
            $rows[] = [
                'id' => $review->id,
                'product' => Html::tag('div',Html::a($review->product->title, $review->product->getCpEditUrl())),
                'rating' => $review->rating,
                'comment' => $review->comment ?: 'No feedback',
                'reviewer' =>  Html::tag('div',Html::a($review->reviewer->fullName ?: $review->reviewer->username, $review->reviewer->getCpEditUrl()))
                ];
        }
        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($currentPage, $total, $limit),
            'data' => $rows
        ]);
    }
}