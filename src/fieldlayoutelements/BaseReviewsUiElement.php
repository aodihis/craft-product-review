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
 * parts of a review to render. Plugin::registerFieldLayoutUiElements() decides which are offered
 * to a given field layout, so a subclass is never listed on a layout it cannot resolve reviews for.
 *
 * The list is paged rather than truncated. The first page is rendered inline with the edit screen;
 * later pages are fetched from ReviewCpController::actionGetElementReviews(), which resolves the
 * subclass back through self::SOURCES and re-renders the same card partial.
 */
abstract class BaseReviewsUiElement extends BaseUiElement
{
    /**
     * Reviews rendered per page.
     *
     * A popular product can hold thousands of reviews, and this sits inside an edit screen that
     * has other work to do, so the panel pages through them instead of listing them all.
     */
    public const PAGE_SIZE = 10;

    /**
     * Characters of a comment shown before it is collapsed behind a "Show more" toggle.
     *
     * Counted against the comment's plain text, not its stored HTML, so the length a reader sees
     * is the length being measured. Reviews vary from a few words to several paragraphs, and
     * without a cap one long comment pushes every other review off the screen.
     */
    public const COMMENT_MAX_LENGTH = 300;

    /**
     * The UI element for each source key, for the paging endpoint.
     *
     * The key travels to the browser and back, so this doubles as the allow-list: a key that is
     * not here resolves to nothing, and the endpoint rejects the request rather than accepting
     * criteria from the client.
     *
     * @var array<string, class-string<self>>
     */
    public const SOURCES = [
        'product' => ProductReviews::class,
        'order' => OrderReviews::class,
        'user' => UserReviews::class,
    ];

    /**
     * Returns the UI element for a source key, or null if the key is not one of self::SOURCES.
     */
    public static function fromSourceKey(string $key): ?self
    {
        $class = self::SOURCES[$key] ?? null;

        return $class !== null ? new $class() : null;
    }

    /**
     * The element class this UI element belongs to.
     *
     * @return class-string<ElementInterface>
     */
    abstract public static function elementType(): string;

    /**
     * The key identifying this UI element in self::SOURCES.
     */
    abstract public static function sourceKey(): string;

    /**
     * Returns the criteria that selects the reviews for the given element ID.
     *
     * Keyed off the ID rather than the element so the paging endpoint can build the same criteria
     * without re-resolving the element. That matters on an edit screen, where the element in hand
     * is often a provisional draft whose ID is its own rather than the canonical one's — the panel
     * would page against a different element than it first rendered.
     *
     * @return array in the shape accepted by services\Reviews::getReviews()
     */
    abstract protected function criteria(int $elementId): array;

    /**
     * Returns the parts of a review to render, from: product, reviewer, rating, comment, status,
     * date.
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
     * Returns how many reviews the panel for the given element ID has to page through.
     */
    public function totalReviews(int $elementId): int
    {
        return Plugin::getInstance()->getReviews()->getTotalReviews($this->criteria($elementId));
    }

    /**
     * Renders one page of review cards.
     *
     * Public because the paging endpoint returns this markup directly, so the cards keep a single
     * definition rather than being rebuilt in JavaScript from JSON.
     *
     * @param int $page 1-based
     * @throws Throwable if the template cannot be rendered
     */
    public function cardsHtml(int $elementId, int $page): string
    {
        $reviews = Plugin::getInstance()->getReviews()->getReviews(
            $this->criteria($elementId),
            'dateCreated DESC',
            self::PAGE_SIZE,
            (max($page, 1) - 1) * self::PAGE_SIZE,
        );

        return Craft::$app->getView()->renderTemplate('product-review/_fieldlayoutelements/_review-cards.twig', [
            'reviews' => $reviews,
            'columns' => $this->columns(),
            'maxCommentLength' => self::COMMENT_MAX_LENGTH,
        ], View::TEMPLATE_MODE_CP);
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

        $total = $this->totalReviews($element->id);

        $html = Craft::$app->getView()->renderTemplate('product-review/_fieldlayoutelements/reviews.twig', [
            'heading' => $this->selectorLabel(),
            'source' => static::sourceKey(),
            'elementId' => $element->id,
            'cards' => $total > 0 ? $this->cardsHtml($element->id, 1) : '',
            'total' => $total,
            'totalPages' => (int)ceil($total / self::PAGE_SIZE),
            'emptyMessage' => $this->emptyMessage(),
        ], View::TEMPLATE_MODE_CP);

        return Html::tag('div', $html, $this->containerAttributes($element, $static));
    }
}
