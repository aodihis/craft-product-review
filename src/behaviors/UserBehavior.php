<?php 

namespace aodihis\productreview\behaviors;

use aodihis\productreview\Plugin;
use yii\base\Behavior;

class UserBehavior extends Behavior
{

    public function getReviewHistory(): array
    {
        $user = $this->owner;
        return Plugin::getInstance()->getReviews()->getReviewHistoryForUser($user->id);
    }

    public function getWaitingToReviewItems()
    {
        $user = $this->owner;
        return Plugin::getInstance()->getReviews()->getItemToReviewForUser($user->id);
    }
    
}