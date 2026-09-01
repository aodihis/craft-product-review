<?php

namespace aodihis\productreview\enums;

/**
 * How a product's `averageRating` is worked out from its submitted reviews.
 */
enum RatingAlgorithm: string
{
    /**
     * The plain mean of the submitted ratings.
     */
    case Average = 'average';

    /**
     * The mean pulled towards the average across every product, by an amount that falls away as the
     * product collects more reviews of its own.
     */
    case Bayesian = 'bayesian';

    /**
     * Resolves a configured value.
     *
     * Anything unset, empty or unrecognised falls back to {@see self::Average}, so a typo in
     * `config/product-review.php` degrades to the original behaviour rather than taking the rating
     * column and the product sort out with it.
     */
    public static function fromSetting(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::Average;
        }

        return self::tryFrom(trim($value)) ?? self::Average;
    }
}
