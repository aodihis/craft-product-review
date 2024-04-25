<?php 

namespace aodihis\craftcommercereview\behaviors;

use aodihis\craftcommercereview\Plugin;
use yii\base\Behavior;

class UserBehavior extends Behavior
{

    public function getReviewHistory(): array
    {
        $user = $this->owner;
        return Plugin::getInstance()->getRevews()->getReviewHistoryForUser($user->id);
    }

    public function getWaitingToReviewItems()
    {
        $user = $this->owner;
        return Plugin::getInstance()->getRevews()->getItemToReviewForUser($user->id);
    }
    
}