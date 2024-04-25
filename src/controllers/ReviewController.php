<?php 

namespace aodihis\craftcommercereview\controllers;

use aodihis\craftcommercereview\models\Review;
use aodihis\craftcommercereview\Plugin;
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

        $review = Plugin::getInstance()->getReviews()->getProductReviews($id);

        if(!$review){
            throw new NotFoundHttpException(Craft::t('commerce-review', "Unable to find review with id: {$id}"));
        }

        if ($review->userId !== $currentUser->getId()) {
            $review->addError("User are not permitted to update this review.");
        }

        if (!Plugin::getInstance()->getReviews()->isReviewCanBeUpdated($review)) {
            $review->addError(Craft::t('commerce-review', "The item are expired to reviewd."));
        }
        $review->updateCount +=1;
        $review->rating     = (int)$this->request->getBodyParam('rating');
        $review->content    = (string)$this->request->getBodyParam('content');
        
        
        if (!$review->validate()) {
            $error = Craft::t('commerce-review', 'Unable to save review.');
            $message = $this->request->getValidatedBodyParam('failMessage') ?? $error;

            return $this->asModelFailure(
                $review,
                $message,
                'review'
            );
        }

    

        if (!Plugin::getInstance()->getReviews()->saveReview($review)) {
            $error = Craft::t('commerce-review', 'Unable to save review.');
            $message = $this->request->getValidatedBodyParam('failMessage') ?? $error;

            return $this->asModelFailure(
                $review,
                $message,
                'review'
            );
        }

        $message = Craft::t('commerce-review', 'Review saved.');
        return $this->asModelSuccess(
            $review,
            $message,
            'review'
        );
    }
}