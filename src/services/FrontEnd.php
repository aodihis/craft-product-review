<?php

namespace aodihis\productreview\services;

use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use craft\base\Component;
use yii\base\InvalidConfigException;

class FrontEnd extends Component
{
    /**
     * @throws InvalidConfigException
     */
    public function getReviews(int $rating = null, ?string $status = Review::STATUS_LIVE,  ?string $sort = 'dateUpdated DESC', int $limit = 10): array
    {
        $criteria = ['status' => $status];
        if ($rating) {
            $criteria['rating'] = $rating;
        }
        return Plugin::getInstance()->reviews->getReviews($criteria, $sort, $limit);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getReviewById(int $id, ?string $status = Review::STATUS_LIVE): ?Review
    {
        return Plugin::getInstance()->reviews->getReviewById($id, $status);
    }

}