<?php

namespace aodihis\productreview\models;

use aodihis\productreview\Plugin;
use Craft;
use craft\base\Model;
use craft\commerce\base\Purchasable;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\services\Purchasables;
use craft\elements\User;
use craft\helpers\UrlHelper;
use DateTime;

/**
 * @property-read User $reviewer
 * @property-read Product $product
 * @property-read string $status
 * @property-read boolean $isEditable
 * @property-read boolean $isPastReviewWindow
 * @property-read boolean $hasReachedEditLimit
 */
class Review extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_LIVE = 'live';
    public const STATUS_EXPIRED = 'expired';

    public ?int $id = null;
    public ?int $productId = null;
    public ?int $orderId = null;
    /** @var int[] */
    public ?array $variantIds = [];

    public int $updateCount = 0;
    public ?int $reviewerId = null;
    public ?int $rating = null;
    public ?string $comment = null;
    public ?DateTIme $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;


    private ?Product $_product = null;
    private ?User $_reviewer = null;

    /** @var Purchasable[]|Variant[] */
    private array $_variants = [];


    public function getProduct(): ?Product
    {
        if ($this->_product) {
            return $this->_product;
        }

        if ($this->productId) {
            $this->_product = Product::find()->id($this->productId)->one();
            return $this->_product;
        }

        return null;

    }

    public function getReviewer(): ?User
    {
        if ($this->_reviewer) {
            return $this->_reviewer;
        }

        if ($this->reviewerId) {
            $this->_reviewer = User::find()->id($this->reviewerId)->one();
            return $this->_reviewer;
        }
        return null;
    }

    /**
     * @params Variant[] $variants
     */
    public function setVariants(array $variants): void
    {
        $this->_variants = $variants;
        $this->variantIds = array_map(static function ($variant) {
            return $variant->id;
        }, $variants);
    }

    public function addVariant(Variant $variant): void
    {
        $this->_variants[] = $variant;
        $this->variantIds[] = $variant->id;
    }


    /**
     * @return Purchasables[]|Variant[]
     */
    public function getVariants(): array
    {
        if ($this->_variants) {
            return $this->_variants;
        }

        if ($this->variantIds) {
            $this->_variants = Variant::find()->id($this->variantIds)->all();
            return $this->_variants;
        }

        return [];
    }

    public function getIsEditable(): bool
    {
        return !$this->getIsPastReviewWindow() && !$this->getHasReachedEditLimit();
    }

    /**
     * Whether this review has been edited as many times as the settings allow.
     */
    public function getHasReachedEditLimit(): bool
    {
        return $this->updateCount > Plugin::getInstance()->getSettings()->maxReviewLimit;
    }

    public function getStatus(): string
    {
        if ($this->updateCount > 0) {
            return self::STATUS_LIVE;
        }

        if ($this->getIsPastReviewWindow()) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_PENDING;
    }

    /**
     * Whether the window for leaving this review has closed.
     *
     * Mirrors the `pending` condition in Reviews::_buildReviewQuery(), which selects rows
     * where `NOW() < dateCreated + maxDaysToReview` — so the window is open up to the
     * deadline and closed once it is reached.
     */
    public function getIsPastReviewWindow(): bool
    {
        $maxDaysToReview = Plugin::getInstance()->getSettings()->maxDaysToReview;

        // 0 means the review window never closes.
        if ($maxDaysToReview === 0) {
            return false;
        }

        // An unsaved review has no creation date, so its window cannot have elapsed.
        if ($this->dateCreated === null) {
            return false;
        }

        // DateTime::modify() mutates in place — clone, or this shifts $this->dateCreated
        // forward every time the status or editability is read.
        $deadline = (clone $this->dateCreated)->modify("+ $maxDaysToReview day");

        return $deadline <= new DateTime('now');
    }

    /**
     * Validates that the review window is still open.
     *
     * This lives in the rules rather than the controller so that a closed window fails like
     * any other validation error — the form re-renders with a message instead of an error
     * page — and so the check also applies on the service path, where saveReview() validates.
     *
     * The edit limit from getIsEditable() is deliberately *not* checked here. The controller
     * increments updateCount before validating, so evaluating the limit at this point would
     * compare the post-increment value and silently tighten the allowance by one. It stays a
     * precondition, checked before the increment.
     */
    public function validateReviewWindow(string $attribute): void
    {
        if ($this->getIsPastReviewWindow()) {
            $this->addError($attribute, Craft::t('product-review', 'This item can no longer be reviewed.'));
        }
    }

    public function getCpViewUrl(): string
    {
        return UrlHelper::cpUrl("product-review/review/$this->id");
    }

    protected function defineRules(): array
    {
        $maxRating = Plugin::getInstance()->getSettings()->maxRating;
        $rules = parent::defineRules();
        $rules[] = [['id', 'productId', 'orderId', 'reviewerId', 'rating', 'updateCount', 'dateCreated', 'dateUpdated'], 'safe'];
        $rules[] = [['productId', 'orderId', 'variantIds', 'reviewerId'], 'required'];
        $rules[] = ['rating', 'integer', 'min' => 1, 'max' => $maxRating, 'when' => function ($model) {
            return $model->updateCount > 0;
        }];
        $rules[] = ['updateCount', 'validateReviewWindow'];
        return $rules;
    }

}