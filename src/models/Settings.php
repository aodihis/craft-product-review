<?php

namespace aodihis\craftcommercereview\models;

use Craft;
use craft\base\Model;

/**
 * Commerce Review settings
 */
class Settings extends Model
{
    public static int $maxRating = 5;
    public static int $maxReviewLimit = 1;
    // Maximum days to leave review after order completed.
    public int $maxDaysToReview = 30;

    public ?string $reviewOnOrderStatus = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['maxDaysToReview', 'reviewOnOrderStatus'], 'required'];
        $rules[] = [['maxDaysToReview'], 'number'];
        return $rules;
    }
}
