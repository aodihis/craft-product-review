<?php
namespace aodihis\craftcommercereview\plugin;

use aodihis\craftcommercereview\services\Reviews;

/**
 * @property Reviews $reviews
 */
trait Services {

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