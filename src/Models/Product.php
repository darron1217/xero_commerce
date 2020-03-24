<?php

namespace Xpressengine\Plugins\XeroCommerce\Models;

use App\Facades\XeMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Xpressengine\Category\Models\Category;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Plugins\XeroCommerce\Handlers\ProductCategoryHandler;
use Xpressengine\Plugins\XeroCommerce\Services\ProductCategoryService;
use Xpressengine\Tag\Tag;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;
use Xpressengine\Plugins\XeroCommerce\Models\Products\GeneralProduct;
use Xpressengine\Plugins\XeroCommerce\Models\Products\DigitalProduct;
use Xpressengine\Plugins\XeroCommerce\Models\Products\TimeProduct;
use Xpressengine\Plugins\XeroCommerce\Models\Products\BundleProduct;

class Product extends SellType
{
    use SoftDeletes, SingleTableInheritanceTrait;

    const IMG_MAXSIZE = 50000000;

    const DISPLAY_VISIBLE = 1;
    const DISPLAY_HIDDEN = 2;

    const DEAL_ON_SALE = 1;
    const DEAL_PAUSE = 2;
    const DEAL_END = 3;

    const TAX_TYPE_TAX = 1;
    const TAX_TYPE_NO = 2;
    const TAX_TYPE_FREE = 3;

    protected $table = 'xero_commerce_products';

    protected $fillable = ['shop_id', 'type', 'product_code', 'name', 'original_price', 'sell_price', 'discount_percentage',
        'min_buy_count', 'max_buy_count', 'description', 'badge_id', 'tax_type', 'state_display',
        'state_deal', 'sub_name', 'shop_delivery_id'];

    protected static $singleTableTypeField = 'type';
    
    public static $singleTableType = 'general';
    
    public static $singleTableName = '일반 상품';
    
    protected static $singleTableSubclasses = [DigitalProduct::class, TimeProduct::class, BundleProduct::class];
    
    /**
     * 타입에 따른 이름을 가져오는 함수.
     * @return array the type map
     */
    public static function getSingleTableNameMap() {
        $nameMap = [self::$singleTableType => self::$singleTableName];
        $subclasses = self::getSingleTableTypeMap();
        foreach ($subclasses as $type => $subclass) {
            $nameMap[$type] = $subclass::$singleTableName;
        }
        return $nameMap;
    }
    
    /**
     * @return array
     */
    public static function getDisplayStates()
    {
        return [
            self::DISPLAY_VISIBLE => '출력',
            self::DISPLAY_HIDDEN => '숨김'
        ];
    }

    /**
     * @return array
     */
    public static function getDealStates()
    {
        return [
            self::DEAL_ON_SALE => '판매중',
            self::DEAL_PAUSE => '판매 일시 중지',
            self::DEAL_END => '거래 종료',
        ];
    }

    /**
     * @return array
     */
    public static function getTaxTypes()
    {
        return [
            self::TAX_TYPE_TAX => '과세',
            self::TAX_TYPE_NO => '비과세',
            self::TAX_TYPE_FREE => '면세',
        ];
    }

    /**
     * @return string
     */
    public function getTaxTypeName()
    {
        $taxType = self::getTaxTypes();

        return $taxType[$this->tax_type];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productOption()
    {
        return $this->hasMany(ProductOptionItem::class, 'product_id', 'id');
    }

    function getJsonFormat()
    {
        $array = parent::getJsonFormat(); // TODO: Change the autogenerated stub
        $array['labels'] = $this->labels;
        $array['badge'] = $this->badge;
        $array['categorys'] = $this->getCategorys();
        return $array;
    }

    function getCategorys()
    {
        $productCategoryService = new ProductCategoryService();
        return $productCategoryService->getProductCategoryTree($this->id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getInfo()
    {
        return $this->sub_name;
    }

    public function getFare()
    {
        return $this->getDelivery()->delivery_fare;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getShop()
    {
        return $this->shop;
    }

    public function getThumbnailSrc($size = 'M')
    {
        $url = 'https://via.placeholder.com/150x120';
        $imageItem = $this->images->first();
        if ($imageItem == null) {
            return $url;
        }
        return XeMedia::images()->getThumbnail($imageItem, 'widen', $size)->url();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sellUnits()
    {
        return $this->hasMany(ProductOptionItem::class, 'product_id');
    }

    /**
     * @return callable
     */
    public function getCountMethod()
    {
        return function ($sellGroupCollection) {
            return $sellGroupCollection->sum(function (SellGroup $sellGroup) {
                return $sellGroup->getCount();
            });
        };
    }

    /**
     * @return callable
     */
    public function getOriginalPriceMethod()
    {
        return function ($sellGroupCollection) {
            return $sellGroupCollection->sum(function (SellGroup $sellGroup) {
                return $sellGroup->getOriginalPrice();
            });
        };
    }

    /**
     * @return callable
     */
    public function getSellPriceMethod()
    {
        return function ($sellGroupCollection) {
            return $sellGroupCollection->sum(function (SellGroup $sellGroup) {
                return $sellGroup->getSellPrice();
            });
        };
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'taggables', 'taggable_id', 'tag_id');
    }

    public function getSlug()
    {
        return $this->slug->slug;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function slug()
    {
        return $this->belongsTo(ProductSlug::class, 'id', 'target_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function productSlug()
    {
        return $this->hasOne(ProductSlug::class, 'target_id');
    }

    public function labels()
    {
        return $this->hasManyThrough(Label::class, ProductLabel::class, 'product_id', 'id', 'id', 'label_id');
    }

    public function badge()
    {
        return $this->hasOne(Badge::class, 'id', 'badge_id');
    }

    function getContents()
    {
        return '';
    }

    public function getStock()
    {
        return $this->sellUnits()->sum('stock');
    }

    public function category()
    {
        return $this->hasManyThrough(CategoryItem::class, ProductCategory::class, 'product_id', 'id','id','category_id');
    }

    public function qna()
    {
        return $this->morphMany(Qna::class,'type');
    }

    public function feedback()
    {
        return $this->morphMany(FeedBack::class,'type');
    }

    public function slugUrl()
    {
        return route('xero_commerce::product.show', ['strSlug' => $this->getSlug()]);
    }

    function renderForSellSet(SellSet $sellSet)
    {
        $row = [];
        $row [] = '<a target="_blank' . now()->toTimeString() . '" href="' . route('xero_commerce::product.show', ['strSlug' => $this->getSlug()]) . '">' . $sellSet->renderSpanBr($this->getName()) . '</a>';
        $row [] = $sellSet->renderSpanBr($this->getInfo());
        $sellSet->sellGroups->each(function (SellGroup $group) use (&$row,$sellSet) {
            $row [] = $sellSet->renderSpanBr($group->forcedSellUnit()->getName() . ' / ' . $group->getCount() . '개', "color: grey");
        });

        $row [] = $sellSet->renderSpanBr($this->getShop()->shop_name);

        return $row;
    }

    function isDelivered()
    {
        return true;
    }
}
