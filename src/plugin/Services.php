<?php

namespace aodihis\productreview\plugin;

use aodihis\productreview\services\Reviews;

/**
 * @property Reviews $reviews
 */
trait Services
{

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