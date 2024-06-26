<?php

namespace aodihis\productreview\models;

use aodihis\productreview\Plugin;
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
 */
class Review extends Model
{
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
        $currentTime = new DateTime("now");
        $maxDaysToReview = Plugin::getInstance()->getSettings()->maxDaysToReview;
        $reviewDateCreated = $this->dateCreated;

        if (($maxDaysToReview !== 0) && ($reviewDateCreated === null || ($reviewDateCreated->modify("+ $maxDaysToReview day") > $currentTime))) {
            return false;
        }

        if ($this->updateCount > Plugin::getInstance()->getSettings()->maxReviewLimit) {
            return false;
        }

        return true;
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
        return $rules;
    }

}