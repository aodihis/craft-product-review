<?php

namespace aodihis\productreview\behaviors;

use aodihis\productreview\models\Review as ModelsReview;
use aodihis\productreview\Plugin;
use craft\elements\User;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

class UserBehavior extends Behavior
{

    /**
     * @throws InvalidConfigException
     */
    public function getReviewHistory(): array
    {
        /** @var User $user */
        $user = $this->owner;
        return Plugin::getInstance()->getReviews()->getReviewHistoryForUser($user->id);
    }

    /**
     * @throws InvalidConfigException
     * @return ModelsReview[]
     */
    public function getWaitingToReviewItems(): array
    {
        /** @var User $user */
        $user = $this->owner;
        return Plugin::getInstance()->getReviews()->getItemToReviewForUser($user->id);
    }

}