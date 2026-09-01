<?php

namespace aodihis\productreview\models;

use aodihis\productreview\enums\RatingAlgorithm;
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

    /**
     * Which algorithm works out a product's `averageRating`, as one of the
     * {@see RatingAlgorithm} values.
     *
     * Null or empty means `average`, the plain mean, so an existing site keeps the ratings it
     * already shows until it opts into something else.
     */
    public ?string $ratingAlgorithm = null;

    /**
     * How much weight the site-wide average carries when `ratingAlgorithm` is `bayesian`, expressed
     * as a number of reviews. Ignored by every other algorithm.
     *
     * A product with this many reviews sits halfway between its own mean and the site-wide one; one
     * with far more is barely moved. Raising it is harsher on products with few reviews. 0 disables
     * the adjustment entirely, leaving the plain mean.
     */
    public int $bayesianPriorWeight = 10;

    /**
     * Returns the algorithm to use, resolving the unset and unrecognised cases.
     *
     * Named so it does not shadow the `$ratingAlgorithm` property, which stays a plain string
     * because that is what project config stores.
     */
    public function resolveRatingAlgorithm(): RatingAlgorithm
    {
        return RatingAlgorithm::fromSetting($this->ratingAlgorithm);
    }

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
        // Negatives would put the review deadline before the review was created, expiring every
        // review the moment it is made.
        $rules[] = [['maxDaysToReview'], 'number', 'min' => 0];
        $rules[] = [['maxCharactersPerReview'], 'integer', 'min' => 0];
        // Left empty on purpose for sites that never set it, which the `in` validator skips.
        $rules[] = [
            ['ratingAlgorithm'],
            'in',
            'range' => array_column(RatingAlgorithm::cases(), 'value'),
        ];
        // A negative weight would drag ratings away from the site average rather than towards it,
        // and takes the divisor towards zero.
        $rules[] = [['bayesianPriorWeight'], 'integer', 'min' => 0];
        return $rules;
    }
}
