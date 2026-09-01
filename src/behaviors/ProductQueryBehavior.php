<?php

namespace aodihis\productreview\behaviors;

use aodihis\productreview\db\Table;
use aodihis\productreview\enums\RatingAlgorithm;
use aodihis\productreview\Plugin;
use craft\commerce\elements\db\ProductQuery;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use yii\base\Behavior;
use yii\db\Expression;

class ProductQueryBehavior extends Behavior
{

    public function events(): array
    {
        return [
            ElementQuery::EVENT_AFTER_PREPARE => 'afterPrepare',
        ];
    }

    public function afterPrepare(): void
    {
        /** @var ProductQuery $productQuery */
        $productQuery = $this->owner;

        $reviewAverageQuery = (new Query())
            ->select([
                'averageRating' => new Expression($this->averageRatingExpression()),
                'productId' => 'productId',
            ])
            ->from([Table::PRODUCT_REVIEW_REVIEWS . ' reviews'])
            ->groupBy(['reviews.productId']);

        $productQuery->subQuery->leftJoin(['reviews' => $reviewAverageQuery], '[[reviews.productId]] = [[commerce_products.id]]');
        $productQuery->subQuery->addSelect('reviews.averageRating as averageRating');
        $productQuery->query->addSelect('subquery.averageRating as averageRating');
    }

    /**
     * Builds the aggregate that produces `averageRating`, for whichever algorithm the site has
     * configured.
     */
    private function averageRatingExpression(): string
    {
        $settings = Plugin::getInstance()->getSettings();

        return match ($settings->resolveRatingAlgorithm()) {
            RatingAlgorithm::Bayesian => $this->bayesianExpression($settings->bayesianPriorWeight),
            RatingAlgorithm::Average => 'Coalesce(CAST(AVG(rating) as decimal(10,2)),0)',
        };
    }

    /**
     * Builds the Bayesian aggregate: `(sum of ratings + m * C) / (number of ratings + m)`, where
     * `C` is the mean across every rated review on the site and `m` is the prior weight.
     *
     * A product with no rating at all short-circuits to 0 rather than to `C`. Reviews are created
     * empty and filled in later, so a product awaiting its first rating would otherwise inherit the
     * site average and outrank products that have genuinely been rated poorly — and it would sort
     * differently from a product with no reviews at all, which the left join already scores 0.
     */
    private function bayesianExpression(int $priorWeight): string
    {
        // Interpolated rather than bound: the value is an int, so there is nothing to inject, and a
        // bound parameter would have to survive being nested in a subquery that is then joined.
        $priorWeight = max(0, $priorWeight);

        $siteAverage = sprintf(
            '(SELECT Coalesce(AVG(rated.rating), 0) FROM %s rated WHERE rated.rating IS NOT NULL)',
            Table::PRODUCT_REVIEW_REVIEWS
        );

        // SUM() is cast before dividing so the division stays exact on databases where integer
        // over integer truncates.
        return sprintf(
            'CASE WHEN COUNT(reviews.rating) = 0 THEN 0 ELSE CAST((CAST(SUM(reviews.rating) as decimal(10,4)) + (%1$d * %2$s)) / (COUNT(reviews.rating) + %1$d) as decimal(10,2)) END',
            $priorWeight,
            $siteAverage
        );
    }
}
