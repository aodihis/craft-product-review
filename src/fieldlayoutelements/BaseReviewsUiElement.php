<?php

namespace aodihis\productreview\fieldlayoutelements;

use aodihis\productreview\Plugin;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseUiElement;
use craft\helpers\Html;
use craft\web\View;
use Throwable;

/**
 * Base class for the read-only review lists that can be added to a field layout.
 *
 * These are UI elements rather than fields on purpose: reviews are written by customers on the
 * front end, so there is nothing for an author to fill in here, and nothing to store on the
 * element being edited. Making it a UI element also leaves the decision with the developer, who
 * drags it into the field layout for the product types, orders or users that should show it,
 * instead of the plugin forcing a panel onto every edit screen.
 *
 * Each subclass says which element type it belongs to, which reviews to look up for it, and which
 * columns to render. Plugin::registerFieldLayoutUiElements() decides which are offered to a given
 * field layout, so a subclass is never listed on a layout it cannot resolve reviews for.
 */
abstract class BaseReviewsUiElement extends BaseUiElement
{
    /**
     * Rows rendered before the list is truncated.
     *
     * A popular product can hold thousands of reviews, and this sits inside an edit screen that
     * has other work to do. The full count is still reported below the table so a truncated list
     * cannot be mistaken for the whole story.
     */
    public const LIMIT = 20;

    /**
     * The element class this UI element belongs to.
     *
     * @return class-string<ElementInterface>
     */
    abstract protected static function elementType(): string;

    /**
     * Returns the criteria that selects the reviews for the given element.
     *
     * @return array in the shape accepted by services\Reviews::getReviews()
     */
    abstract protected function criteria(ElementInterface $element): array;

    /**
     * Returns the columns to render, from: product, reviewer, rating, comment, status, date.
     *
     * @return string[]
     */
    abstract protected function columns(): array;

    /**
     * Returns the message shown when the element has no reviews.
     */
    abstract protected function emptyMessage(): string;

    /**
     * @inheritdoc
     *
     * Listing the same reviews twice in one layout says nothing the first list did not.
     */
    public function isMultiInstance(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function hasCustomWidth(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function selectorIcon(): ?string
    {
        return 'comment-lines';
    }

    /**
     * @inheritdoc
     * @throws Throwable if the template cannot be rendered
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        // No element, or one that has never been saved: the field layout designer renders the
        // selector rather than this, so the only way to get here without an ID is a brand new
        // element, which cannot have been reviewed yet.
        if ($element === null || $element->id === null) {
            return null;
        }

        // A layout is only offered the subclass matching its type, but layouts are shared and
        // reused. Commerce hands the product layout's saved config to variants, for one. Bail
        // rather than resolve an ID against the wrong table and show unrelated reviews.
        $elementType = static::elementType();

        if (!$element instanceof $elementType) {
            return null;
        }

        // Same gate as the plugin's own control panel section. Being able to edit a product does
        // not imply being allowed to read what customers wrote about it.
        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_REVIEWS)) {
            return null;
        }

        $reviews = Plugin::getInstance()->getReviews();
        $criteria = $this->criteria($element);

        $html = Craft::$app->getView()->renderTemplate('product-review/_fieldlayoutelements/reviews.twig', [
            'heading' => $this->selectorLabel(),
            'reviews' => $reviews->getReviews($criteria, 'dateCreated DESC', self::LIMIT),
            'total' => $reviews->getTotalReviews($criteria),
            'columns' => $this->columns(),
            'emptyMessage' => $this->emptyMessage(),
        ], View::TEMPLATE_MODE_CP);

        return Html::tag('div', $html, $this->containerAttributes($element, $static));
    }
}
