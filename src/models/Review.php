<?php
namespace aodihis\productreview\models;

use aodihis\productreview\Plugin;
use aodihis\productreview\records\Review as RecordsReview;
use craft\base\Model;
use craft\commerce\base\Purchasable;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use craft\elements\User;
use craft\validators\UniqueValidator;
use DateTime;

class Review extends Model
{
    public ?int $id                 = null;
    public ?int $productId          = null;
    
    /** @var int[] */
    public ?array $variantIds      = [];

    public int $updateCount         = 0;
    public ?int $userId             = null;
    public ?int $rating             = null;
    public ?string $content         = null;
    public ?DateTIme $dateCreated   = null;
    public ?DateTime $dateUpdated   = null;
    public ?string $uid             = null;


    private ?Product $_product = null;
    private ?User $_user = null;

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

        // if ($this->_variants) {
        //     $this->_product = $this->_variants[0]->owner;
        //     return $this->_product;
        // }

        // if ($this->variantIds) {
        //     $variants = $this->getVariants();
        //     $this->_product = $variants[0]->owner;
        //     return $this->_product;
        // }

        return null;
        
    }

    public function getUser(): ?User
    {
        if ($this->_user) {
            return $this->_user;
        }

        if ($this->userId) {
            $this->_user = User::find()->id($this->userId)->one();
            return $this->_user;
        } 

        $order = $this->getOrder();
        $this->_user = $order ? $order->getCustomer() : null;
        return $this->_user;
    }

    /**
     * @params Variant[] $variants
     */
    public function setVariants(array $variants) : void {
        $this->_variants = $variants;
        $this->variantIds = array_map(static function($variant) {
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
            $this->_variants = Purchasable::find()->id($this->variantIds);
            return  $this->_variants;
        }

        return [];
    }



    protected function defineRules(): array
    {
        $maxRating = Plugin::getInstance()->getSettings()->maxRating;
        $rules = parent::defineRules();
        $rules[] = [['id', 'productId', 'userId', 'rating', 'updateCount'], 'safe'];
        $rules[] = [['productId', 'lineItemIds', 'userId'], 'required'];
        $rules[] = ['rating', 'integer', 'min' => 1, 'max' => $maxRating, 'when' => function($model) {
            return $model->updateCount > 0;
        }];
        return $rules;
    }

}