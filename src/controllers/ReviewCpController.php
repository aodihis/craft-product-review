<?php

namespace aodihis\productreview\controllers;


use aodihis\productreview\fieldlayoutelements\BaseReviewsUiElement;
use aodihis\productreview\models\Review;
use aodihis\productreview\Plugin;
use Craft;
use craft\helpers\AdminTable;
use craft\web\Controller;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ReviewCpController extends Controller
{
    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Every action here reads review data, including the two search endpoints, which
        // otherwise expose user and product records to anyone who can reach the control panel.
        $this->requirePermission(Plugin::PERMISSION_VIEW_REVIEWS);

        return true;
    }

    public function actionIndex(): Response
    {
        $maxRating = Plugin::getInstance()->getSettings()->getMaxRating();
        return $this->renderTemplate('product-review/index', compact('maxRating'));
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionView(int $id): Response
    {
        $review = Plugin::getInstance()->getReviews()->getReviewById($id);
        return $this->renderTemplate('product-review/_view', compact('review'));
    }

    /**
     * Returns one page of review cards for a field layout panel.
     *
     * The panel renders its first page inline with the edit screen; this serves the rest. It
     * returns rendered markup rather than JSON rows so the cards keep a single definition in
     * `_review-cards.twig`, instead of being rebuilt a second time in JavaScript.
     *
     * `source` names the UI element, and is checked against BaseReviewsUiElement::SOURCES, so the
     * criteria are always built by the plugin. The client only ever chooses which of the three
     * panels it is, and which element ID to page — never what to select on.
     *
     * @throws BadRequestHttpException if the request is not for JSON, or names no known source
     * @throws Throwable if the cards cannot be rendered
     */
    public function actionGetElementReviews(): Response
    {
        $this->requireAcceptsJson();

        $source = (string)$this->request->getRequiredParam('source');
        $elementId = (int)$this->request->getRequiredParam('elementId');
        $page = max(1, (int)$this->request->getParam('page', 1));

        $uiElement = BaseReviewsUiElement::fromSourceKey($source);

        if ($uiElement === null) {
            throw new BadRequestHttpException("Unknown review source: $source");
        }

        // Recounted per request rather than trusted from the client: reviews can land between
        // pages, and the footer has to agree with what is actually there.
        $total = $uiElement->totalReviews($elementId);
        $totalPages = (int)ceil($total / BaseReviewsUiElement::PAGE_SIZE);

        // A page past the end returns the last one instead of an empty list, which is what a stale
        // footer or a deleted review would otherwise leave the reader looking at.
        $page = $totalPages > 0 ? min($page, $totalPages) : 1;

        return $this->asJson([
            'html' => $total > 0 ? $uiElement->cardsHtml($elementId, $page) : '',
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    /**
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionGetTableData(): Response
    {
        $this->requireAcceptsJson();

        $limit = 10;
        $currentPage = $this->request->getParam('page', 1);
        $offset = ($currentPage - 1) * $limit;

        $filterProductId = (int)$this->request->getParam('productId') ?: null;
        $filterReviewerId = (int)$this->request->getParam('reviewerId') ?: null;
        $filterRating = $this->request->getParam('rating') ?: null;
        $criteria = ['status' => 'live'];
        if ($filterProductId) {
            $criteria['productId'] = $filterProductId;
        }
        if ($filterReviewerId) {
            $criteria['reviewerId'] = $filterReviewerId;
        }
        if ($filterRating) {
            $criteria['rating'] = $filterRating;
        }
        /** @var Review[] $reviews */
        $reviews = Plugin::getInstance()->getReviews()->getReviews($criteria, 'dateCreated DESC', 10, $offset);
        $total = Plugin::getInstance()->getReviews()->getTotalReviews($criteria);

        $rows = [];
        foreach ($reviews as $review) {
            // Both can be null while the review row still exists: a soft-deleted user or product
            // stays in the trash until garbage collection, so the row's foreign key has not
            // cascaded yet but the element no longer resolves.
            $product = $review->getProduct();
            $reviewer = $review->getReviewer();

            $rows[] = [
                'id' => $review->id,
                'product' => [
                    'title' => $product?->title ?: Craft::t('product-review', 'Removed product'),
                    'cpEditUrl' => $product?->getCpEditUrl() ?: '',
                ],
                'rating' => $review->rating,
                // Tags stripped, not the stored HTML: the column truncates and the table writes
                // it through innerHTML, so markup would either be escaped into visible tags or
                // sliced mid-element. The JS escapes this again before rendering.
                'comment' => strip_tags((string)$review->comment) ?: Craft::t('product-review', 'No feedback'),
                'reviewer' => [
                    // Guest customers have neither a username nor a name, so fall back to the
                    // email — otherwise their reviews render with a blank author.
                    'name' => $reviewer
                        ? ($reviewer->fullName ?: ($reviewer->username ?: $reviewer->email))
                        : Craft::t('product-review', 'Deleted user'),
                    'cpEditUrl' => $reviewer?->getCpEditUrl() ?: '',
                ],
                'url' => $review->getCpViewUrl(),
            ];
        }
        return $this->asJson([
            'pagination' => AdminTable::paginationLinks($currentPage, $total, $limit),
            'data' => $rows
        ]);
    }
}