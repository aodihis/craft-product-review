<?php

namespace aodihis\productreview\models;

use craft\base\Model;

/**
 * Product Review settings
 * @property-read int $maxReviewLimit
 * @property-read int $maxRating
 */
class Settings extends Model
{
    public static int $defaultMaxRating = 5;
    public static int $defaultMaxReviewLimit = 1;
    // Maximum days to leave review after order completed.
    public int $maxDaysToReview = 30;

    /**
     * Characters a reviewer may write in a comment. 0 means no limit.
     *
     * Counted against what the reviewer typed, before the comment is run through HTML Purifier,
     * so the number here is the number they see in their own textarea. Purifying can lengthen a
     * comment — a typed `&` is held as `&amp;` — and rejecting a comment for characters the
     * reviewer never wrote would be impossible to act on.
     *
     * Defaults to 0 so upgrading does not start rejecting comments a site was happily taking.
     */
    public int $maxCharactersPerReview = 0;

    public ?string $orderStatusToReview = null;

    public function getMaxRating(): int
    {
        return Settings::$defaultMaxRating;
    }

    public function getMaxReviewLimit(): int
    {
        return self::$defaultMaxReviewLimit;
    }


    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['maxDaysToReview', 'orderStatusToReview'], 'required'];
        $rules[] = [['maxDaysToReview'], 'number'];
        $rules[] = [['maxCharactersPerReview'], 'integer', 'min' => 0];
        return $rules;
    }
}
