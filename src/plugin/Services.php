<?php

namespace aodihis\productreview\plugin;

use aodihis\productreview\services\Reviews;
use yii\base\InvalidConfigException;

/**
 * @property-read Reviews $reviews
 */
trait Services
{

    /**
     * @throws InvalidConfigException
     */
    public function getReviews(): Reviews
    {
        return $this->get('reviews');
    }

    private function _registerComponents(): void
    {
        $this->setComponents([
            'reviews' => Reviews::class
        ]);
    }
}