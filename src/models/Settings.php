<?php

namespace aodihis\productreview\models;

use craft\base\Model;

/**
 * Commerce Review settings
 * @property-read int $maxReviewLimit
 * @property-read int $maxRating
 */
class Settings extends Model
{
    public static int $maxRating = 5;
    public static int $maxReviewLimit = 1;
    // Maximum days to leave review after order completed.
    public int $maxDaysToReview = 30;

    public ?string $orderStatusToReview = null;

    public function getMaxRating(): int
    {
        return self::$maxRating;
    }

    public function getMaxReviewLimit(): int
    {
        return self::$maxReviewLimit;
    }


    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['maxDaysToReview', 'orderStatusToReview'], 'required'];
        $rules[] = [['maxDaysToReview'], 'number'];
        return $rules;
    }
}
