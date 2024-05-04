<?php 

namespace aodihis\productreview\controllers;

use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use aodihis\productreview\web\assets\reviewtable\ReviewTableAsset;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\web\Controller;
use craft\web\Response;
use yii\web\NotFoundHttpException;
use craft\helpers\AdminTable;
use craft\helpers\Html;

class ReviewCpController extends Controller
{


    /**
     * @return Response
     * @throws BadRequestHttpException
     * @since 4.0
     */
    public function actionCustomerSearch(): Response
    {
        $this->requireAcceptsJson();

        $query = $this->request->getQueryParam('query');

        $limit = 30;
        $customers = [];

        if ($query === null) {
            return $this->asJson($customers);
        }

        $userQuery = User::find()->status(null)->limit($limit);

        if ($query) {
            $userQuery->search(urldecode($query));
        }

        $customers = $userQuery->collect()->map(function(User $user) {
            return $this->_customerToArray($user);
        });

        return $this->asSuccess(data: compact('customers'));
    }

    
    public function actionGetUser()
    {

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
                'reviewer' =>  Html::tag('div',Html::a($review->user->fullName ?: $review->user->username, $review->user->getCpEditUrl()))
                ];
        }
        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($currentPage, $total, $limit),
            'data' => $rows
        ]);
    }
}