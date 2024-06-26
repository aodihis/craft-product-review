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
    public function getAllReviews(int $rating = null, ?string $sort = 'dateUpdated DESC', int $limit = 10): array
    {
        return Plugin::getInstance()->reviews->getReviews(null, null, $rating, $sort, $limit);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getReviewById(int $id): ?Review
    {
        return Plugin::getInstance()->reviews->getReviewById($id);
    }
}