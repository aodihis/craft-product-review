<?php

namespace aodihis\productreview\controllers;

use aodihis\productreview\Plugin;
use Craft;
use craft\web\Controller;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ReviewController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    /**
     * @throws InvalidConfigException
     * @throws MethodNotAllowedHttpException
     * @throws BadRequestHttpException
     * @throws Throwable
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $currentUser = Craft::$app->getUser()->getIdentity();

        $id = (int)$this->request->getRequiredBodyParam('id');
        $rating = (int)$this->request->getBodyParam('rating');
        $comment = (string)$this->request->getBodyParam('comment');

        $review = Plugin::getInstance()->getReviews()->getReviewById($id, null);

        if (!$review) {
            throw new NotFoundHttpException(Craft::t('product-review', "Unable to find review with id: $id"));
        }

        // These must throw rather than collect errors: validate() below clears the error
        // bag, so anything recorded here would be discarded before it was ever checked.
        if ((int)$review->reviewerId !== (int)$currentUser->id) {
            throw new ForbiddenHttpException(Craft::t('product-review', 'You are not permitted to update this review.'));
        }

        if (!$review->getIsEditable()) {
            throw new BadRequestHttpException(Craft::t('product-review', 'This item can no longer be reviewed.'));
        }

        ++$review->updateCount;
        $review->rating = $rating;
        $review->comment = $comment;

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