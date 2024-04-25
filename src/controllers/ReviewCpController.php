<?php 

namespace aodihis\craftcommercereview\controllers;

use aodihis\craftcommercereview\models\Review;
use aodihis\craftcommercereview\Plugin;
use aodihis\craftcommercereview\web\assets\reviewtable\ReviewTableAsset;
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
                'product' => Html::tag('div',Html::a($review->product->title, $review->product->getCpEditUrl)),
                'rating' => $review->rating,
                'content' => $review->content,
                'reviewer' =>  Html::tag('div',Html::a($review->user->fullName, $review->user->getCpEditUrl))
                ];
        }
        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($currentPage, $total, $limit),
            'data' => $rows
        ]);
    }
}