<?php

namespace aodihis\productreview\controllers;

use aodihis\productreview\Plugin;
use Craft;
use craft\web\Controller;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
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
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $currentUser = Craft::$app->getUser()->getIdentity();

        $id = (int)$this->request->getRequiredBodyParam('id');
        $rating = (int)$this->request->getBodyParam('rating');
        $comment = (string)$this->request->getBodyParam('comment');

        $review = Plugin::getInstance()->getReviews()->getReviewById($id);

        if (!$review) {
            throw new NotFoundHttpException(Craft::t('product-review', "Unable to find review with id: $id"));
        }

        if ($review->reviewerId !== $currentUser->getId()) {
            $review->addError("User are not permitted to update this review.");
        }

        if (!$review->getIsEditable()) {
            $review->addError(Craft::t('product-review', "The item are expired to review."));
        }
        ++$review->updateCount;
        $review->rating = $rating;
        $review->comment = $comment;
        $review->validate();


        if ($review->hasErrors()) {
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