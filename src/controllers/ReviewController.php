<?php 

namespace aodihis\productreview\controllers;

use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\web\Controller;
use craft\web\Response;
use yii\web\NotFoundHttpException;

class ReviewController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionSave()
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $errors = [];
        $currentUser = Craft::$app->getUser()->getIdentity();

        $id    = (int)$this->request->getRequiredBodyParam('id');
        $rating = (int)$this->request->getBodyParam('rating');
        $comment = (string)$this->request->getBodyParam('comment');
        
        $review = Plugin::getInstance()->getReviews()->getReviewById($id);

        if(!$review){
            throw new NotFoundHttpException(Craft::t('product-review', "Unable to find review with id: {$id}"));
        }

        if ($review->reviewerId !== $currentUser->getId()) {
            $review->addError("User are not permitted to update this review.");
        }

        if (!$review->getIsEditable()) {
            $review->addError(Craft::t('product-review', "The item are expired to reviewd."));
        }
        $review->updateCount +=1;
        $review->rating     = $rating;
        $review->comment    = $comment;
        
        if (!$review->validate()) {
            $error = Craft::t('product-review', 'Unable to save review.');
            $message = $this->request->getValidatedBodyParam('failMessage') ?? $error;

            return $this->asModelFailure(
                $review,
                $message,
                'review',
                $review->toArray()
            );
        }


        if (!Plugin::getInstance()->getReviews()->saveReview($review)) {
            $error = Craft::t('product-review', 'Unable to save review.');
            $message = $this->request->getValidatedBodyParam('failMessage') ?? $error;

            return $this->asModelFailure(
                $review,
                $message,
                'review',
                $review->toArray()
            );
        }

        $message = Craft::t('product-review', 'Review saved.');
        return $this->asModelSuccess(
            $review,
            $message,
            'review',
            $review->toArray()
        );
    }
}