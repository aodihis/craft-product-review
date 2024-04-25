<?php
namespace aodihis\craftcommercereview\models;

use aodihis\craftcommercereview\Plugin;
use aodihis\craftcommercereview\records\Review as RecordsReview;
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
    public ?array $lineItemIds      = [];

    public ?int $userId             = null;
    public ?int $rating             = null;
    public ?string $content         = null;
    public ?DateTIme $dateCreated   = null;
    public ?DateTime $dateUpdated   = null;
    public ?string $uid             = null;


    private Product $_product = null;
    private User $_user = null;

    
     /** @var int[] */
     private ?array $_purchasableIds = [];

    /** @var Purchasable[]|Variant[] */
    private array $_purchasables = [];

     /** @var LiteItem[] */
    private array $_lineItems = [];


    public function getProduct(): ?Product
    {
        if ($this->_product) {
            return $this->_product;
        }

        if ($this->productId) {
            $this->_product = Product::find()->id($this->productId)->one();
            return $this->_product; 
        }

        if ($this->_variant) {
            $this->_product = $this->_variant->owner;
            return $this->_product;
        }

        if ($this->variantId) {
            $variant = $this->getVariant();
            $this->_product = $variant->owner;
            return $this->_product;
        }

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
     * @return Purchasables[]|Variant[]
     */
    public function getPurchasables(): array
    {
        if ($this->_purchasables) {
            return $this->_purchasables;
        }

        if ($this->_purchasableIds) {
            $this->_purchasables = Purchasable::find()->id($this->_purchasableIds);
            return  $this->_purchasables;
        }

        $lineItems = $this->getLineItems();
        foreach($lineItems as $lineItem) {
            $this->_purchasableIds[]    = $lineItem->purchasableId;
            $this->_purchasables[]      = $lineItem->purchasable;
        }
        return $this->_purchasables;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        if($this->_lineItems) {
            return $this->_lineItems;
        }

        if ($this->lineItemIds) {
            foreach($this->lineItemIds as $lineItemId) {
                $this->_lineItems[] = Commerce::getInstance()->lineItems->getLineItemById($lineItemId);
            }
        }
        
        return $this->_lineItems;
    }

    /** @param LineItem[] $lineItems */
    public function setLineItems(array $lineItems): void
    {
        $this->_lineItems   = $lineItems;
        $this->lineItemIds  = array_map(static function($lineItem){
            return $lineItem->id;
        },$lineItems);
        foreach ($lineItems as $lineItem) {
            $this->_purchasables[] = $lineItem->purchasable;
            $this->purchasableIds[] = $lineItem->purchasableId;
        };
    }

    public function addLineItems(LineItem $lineItem): void
    {
        $this->_lineItems[] = $lineItem;
        $this->lineItemIds[] = $lineItem->id;
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