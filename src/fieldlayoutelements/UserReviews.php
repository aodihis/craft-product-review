<?php

namespace aodihis\productreview\fieldlayoutelements;

use Craft;
use craft\elements\User;

/**
 * Lists the reviews a customer has been asked for, for the user field layout.
 */
class UserReviews extends BaseReviewsUiElement
{
    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return User::class;
    }

    /**
     * @inheritdoc
     */
    public static function sourceKey(): string
    {
        return 'user';
    }

    /**
     * @inheritdoc
     *
     * Every status, hence the explicit `null`, so the panel covers what this customer has written
     * as well as what they still owe. Reviews are attributed to the inactive account Commerce
     * creates for a guest checkout, so rows can appear here for someone who has never signed in.
     */
    protected function criteria(int $elementId): array
    {
        return [
            'status' => null,
            'reviewerId' => $elementId,
        ];
    }

    /**
     * @inheritdoc
     *
     * No reviewer: every card belongs to the user being edited.
     */
    protected function columns(): array
    {
        return ['product', 'rating', 'comment', 'status', 'date'];
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        return Craft::t('product-review', 'Customer Reviews');
    }

    /**
     * @inheritdoc
     */
    protected function emptyMessage(): string
    {
        return Craft::t('product-review', 'This customer has not been asked for any reviews.');
    }
}
