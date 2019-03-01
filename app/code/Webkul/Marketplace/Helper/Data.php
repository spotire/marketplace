<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Helper;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;

/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var null|array
     */
    protected $_options;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_product;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @param \Magento\Framework\App\Helper\Context        $context
     * @param \Magento\Framework\ObjectManagerInterface    $objectManager
     * @param \Magento\Customer\Model\Session              $customerSession
     * @param CollectionFactory                            $collectionFactory
     * @param HttpContext                                  $httpContext
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Store\Model\StoreManagerInterface   $storeManager
     * @param \Magento\Directory\Model\Currency            $currency
     * @param \Magento\Framework\Locale\CurrencyInterface  $localeCurrency
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        CollectionFactory $collectionFactory,
        HttpContext $httpContext,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency
    ) {
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_collectionFactory = $collectionFactory;
        $this->httpContext = $httpContext;
        $this->_product = $product;
        parent::__construct($context);
        $this->_currency = $currency;
        $this->_localeCurrency = $localeCurrency;
        $this->_storeManager = $storeManager;
    }
    
    /**
     * Return Customer id.
     *
     * @return bool|0|1
     */
    public function getCustomer()
    {
        return $this->_customerSession->getCustomer();
    }
    
    /**
     * Return Customer id.
     *
     * @return bool|0|1
     */
    public function getCustomerId()
    {
        return $this->_customerSession->getCustomerId();
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Return the Customer seller status.
     *
     * @return bool|0|1
     */
    public function isSeller()
    {
        $sellerStatus = 0;
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }

    /**
     * Return the authorize seller status.
     *
     * @return bool|0|1
     */
    public function isRightSeller($productId = '')
    {
        $data = 0;
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
            ->getCollection()
            ->addFieldToFilter(
                'mageproduct_id',
                $productId
            )->addFieldToFilter(
                'seller_id',
                $this->getCustomerId()
            );
        foreach ($model as $value) {
            $data = 1;
        }

        return $data;
    }

    /**
     * Return the seller Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerData()
    {
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        return $model;
    }

    /**
     * Return the seller Product Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductData()
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id',
                $this->getCustomerId()
            );

        return $model;
    }

    /**
     * Return the seller product data by product id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductDataByProductId($productId = '')
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
            ->getCollection()
            ->addFieldToFilter(
                'mageproduct_id',
                $productId
            );
        $websiteId = $this->getWebsiteId();
        $joinTable = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('customer_grid_flat');
        if ($this->getCustomerSharePerWebsite()) {
            $model->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id AND website_id= '.$websiteId
            );
        } else {
            $model->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id'
            );
        }
        return $model;
    }

    /**
     * Return the seller data by seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerDataBySellerId($sellerId = '')
    {
        $model = $this->getSellerCollectionObj($sellerId);
        $websiteId = $this->getWebsiteId();
        $joinTable = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('customer_grid_flat');
        if ($this->getCustomerSharePerWebsite()) {
            $model->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id AND website_id= '.$websiteId
            );
        } else {
            $model->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id'
            );
        }
        return $model;
    }

    /**
     * Return the seller data by seller shop url.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerDataByShopUrl($shopUrl = '')
    {
        $model = $this->getSellerCollectionObjByShop($shopUrl);
        $websiteId = $this->getWebsiteId();
        $joinTable = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('customer_grid_flat');
        if ($this->getCustomerSharePerWebsite()) {
            $model->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id AND website_id= '.$websiteId
            );
        } else {
            $model->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id'
            );
        }
        return $model;
    }

    public function getRootCategoryIdByStoreId($storeId = '')
    {
        return $this->_storeManager->getStore($storeId)->getRootCategoryId();
    }

    public function getAllStores()
    {
        return $this->_storeManager->getStores();
    }

    public function getCurrentStoreId()
    {
        // give the current store id
        return $this->_storeManager->getStore()->getStoreId();
    }

    public function getWebsiteId()
    {
        // give the current store id
        return $this->_storeManager->getStore(true)->getWebsite()->getId();
    }

    public function getAllWebsites()
    {
        // give the current store id
        return $this->_storeManager->getWebsites();
    }

    public function getSingleStoreStatus()
    {
        return $this->_storeManager->hasSingleStore();
    }

    public function getSingleStoreModeStatus()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    public function setCurrentStore($storeId)
    {
        return $this->_storeManager->setCurrentStore($storeId);
    }

    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
        // give the currency code
    }

    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getConfigAllowCurrencies()
    {
        return $this->_currency->getConfigAllowCurrencies();
    }

    /**
     * Retrieve currency rates to other currencies.
     *
     * @param string     $currency
     * @param array|null $toCurrencies
     *
     * @return array
     */
    public function getCurrencyRates($currency, $toCurrencies = null)
    {
        // give the currency rate
        return $this->_currency->getCurrencyRates($currency, $toCurrencies);
    }

    /**
     * Retrieve currency Symbol.
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_localeCurrency->getCurrency(
            $this->getBaseCurrencyCode()
        )->getSymbol();
    }

    /**
     * Retrieve price format.
     *
     * @return string
     */
    public function getPriceFormat()
    {
        return $this->_objectManager->create(
            'Magento\Framework\Locale\Format'
        )->getPriceFormat('', $this->getBaseCurrencyCode());
    }

    /**
     * @return array|null
     */
    public function getAllowedSets()
    {
        if (null == $this->_options) {
            $this->_options = $this->_collectionFactory->create()
                ->addFieldToFilter(
                    'attribute_set_id',
                    ['in' => explode(',', $this->getAllowedAttributesetIds())]
                )
                ->setEntityTypeFilter($this->_product->getTypeId())
                ->toOptionArray();
        }

        return $this->_options;
    }

    /**
     * Options getter.
     *
     * @return array
     */
    public function getAllowedProductTypes()
    {
        $alloweds = explode(',', $this->getAllowedProductType());
        $data = [
            'simple' => __('Simple'),
            'downloadable' => __('Downloadable'),
            'virtual' => __('Virtual'),
            'configurable' => __('Configurable'),
            'grouped' => __('Grouped Product'),
            'bundle' => __('Bundle Product'),
        ];
        $allowedproducts = [];
        if (isset($alloweds)) {
            foreach ($alloweds as $allowed) {
                if (!empty($data[$allowed])) {
                    array_push(
                        $allowedproducts,
                        ['value' => $allowed, 'label' => $data[$allowed]]
                    );
                }
            }
        }

        return $allowedproducts;
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Tax\Model\ClassModel
     */
    public function getTaxClassModel()
    {
        return $this->_objectManager->create('Magento\Tax\Model\ClassModel')
            ->getCollection()
            ->addFieldToFilter('class_type', 'PRODUCT');
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Catalog\Model\Product\Visibility
     */
    public function getVisibilityOptionArray()
    {
        return $this->_objectManager->create(
            'Magento\Catalog\Model\Product\Visibility'
        )->getOptionArray();
    }

    /**
     * Return the Seller existing status.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function isSellerExist()
    {
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        return $model->getSize();
    }

    /**
     * Return the Seller data by customer Id stored in the session.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSeller()
    {
        $data = [];
        $bannerpic = '';
        $logopic = '';
        $countrylogopic = '';
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        $customer = $this->_objectManager->create(
            'Magento\Customer\Model\Customer'
        )->load($this->getCustomerId());
        foreach ($model as $value) {
            $data = $value->getData();
            $bannerpic = $value->getBannerPic();
            $logopic = $value->getLogoPic();
            $countrylogopic = $value->getCountryPic();
            if (strlen($bannerpic) <= 0) {
                $bannerpic = 'banner-image.png';
            }
            if (strlen($logopic) <= 0) {
                $logopic = 'noimage.png';
            }
            if (strlen($countrylogopic) <= 0) {
                $countrylogopic = '';
            }
        }
        $data['banner_pic'] = $bannerpic;
        $data['taxvat'] = $customer->getTaxvat();
        $data['logo_pic'] = $logopic;
        $data['country_pic'] = $countrylogopic;

        return $data;
    }

    /**
     * Return the Seller Model Collection Object.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerCollectionObj($sellerId)
    {        
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id', $sellerId
        )->addFieldToFilter(
            'store_id', $this->getCurrentStoreId()
        );
        // If seller data doesn't exist for current store
        if (!count($model)) {
            $model = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Seller'
            )->getCollection()
            ->addFieldToFilter(
                'seller_id', $sellerId
            )->addFieldToFilter(
                'store_id', 0
            );
        }
        return $model;
    }

    /**
     * Return the Seller Model Collection Object.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerCollectionObjByShop($shopUrl)
    {
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Seller'
        )->getCollection()
        ->addFieldToFilter(
            'is_seller',
            1
        )->addFieldToFilter(
            'shop_url',
            $shopUrl
        )->addFieldToFilter(
            'store_id', $this->getCurrentStoreId()
        );
        // If seller data doesn't exist for current store
        if (!count($model)) {
            $model = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Seller'
            )->getCollection()
            ->addFieldToFilter(
                'is_seller', 1
            )->addFieldToFilter(
                'shop_url', $shopUrl
            )->addFieldToFilter(
                'store_id', 0
            );
        }
        return $model;
    }

    public function getFeedTotal($sellerId)
    {
        $data = [];
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Feedback'
        )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id',
                $sellerId
            );
        $collection->addFieldToFilter(
            'status',
            ['neq' => 0]
        );

        $price = 0;
        $value = 0;
        $quality = 0;
        $totalfeed = 0;
        $feedCount = 0;
        $collectionCount = 1;
        foreach ($collection as $record) {
            $price += $record->getFeedPrice();
            $value += $record->getFeedValue();
            $quality += $record->getFeedQuality();
        }
        $collectionSize = $collection->getSize();
        if ($collectionSize != 0) {
            $feedCount = $collectionSize;
            $collectionCount = $collectionSize;
            $totalfeed = ceil(
                ($price + $value + $quality) / (3 * $collectionCount)
            );
        }
        $priceFiveStarReviewCount = $collection->getAllReviewCount('feed_price', 100);
        $priceFourStarReviewCount = $collection->getAllReviewCount('feed_price', 80);
        $priceThreeStarReviewCount = $collection->getAllReviewCount('feed_price', 60);
        $priceTwoStarReviewCount = $collection->getAllReviewCount('feed_price', 40);
        $priceOneStarReviewCount = $collection->getAllReviewCount('feed_price', 20);
        $priceFiveStarReview = 0;
        $priceFourStarReview = 0;
        $priceThreeStarReview = 0;
        $priceTwoStarReview = 0;
        $priceOneStarReview = 0;
        if (!empty($priceFiveStarReviewCount[0])) {
            $priceFiveStarReview = $priceFiveStarReviewCount[0];
        }
        if (!empty($priceFourStarReviewCount[0])) {
            $priceFourStarReview = $priceFourStarReviewCount[0];
        }
        if (!empty($priceThreeStarReviewCount[0])) {
            $priceThreeStarReview = $priceThreeStarReviewCount[0];
        }
        if (!empty($priceTwoStarReviewCount[0])) {
            $priceTwoStarReview = $priceTwoStarReviewCount[0];
        }
        if (!empty($priceOneStarReviewCount[0])) {
            $priceOneStarReview = $priceOneStarReviewCount[0];
        }
        $valueFiveStarReviewCount = $collection->getAllReviewCount('feed_value', 100);
        $valueFourStarReviewCount = $collection->getAllReviewCount('feed_value', 80);
        $valueThreeStarReviewCount = $collection->getAllReviewCount('feed_value', 60);
        $valueTwoStarReviewCount = $collection->getAllReviewCount('feed_value', 40);
        $valueOneStarReviewCount = $collection->getAllReviewCount('feed_value', 20);
        $valueFiveStarReview = 0;
        $valueFourStarReview = 0;
        $valueThreeStarReview = 0;
        $valueTwoStarReview = 0;
        $valueOneStarReview = 0;
        if (!empty($valueFiveStarReviewCount[0])) {
            $valueFiveStarReview = $valueFiveStarReviewCount[0];
        }
        if (!empty($valueFourStarReviewCount[0])) {
            $valueFourStarReview = $valueFourStarReviewCount[0];
        }
        if (!empty($valueThreeStarReviewCount[0])) {
            $valueThreeStarReview = $valueThreeStarReviewCount[0];
        }
        if (!empty($valueTwoStarReviewCount[0])) {
            $valueTwoStarReview = $valueTwoStarReviewCount[0];
        }
        if (!empty($valueOneStarReviewCount[0])) {
            $valueOneStarReview = $valueOneStarReviewCount[0];
        }

        $qualityFiveStarReviewCount = $collection->getAllReviewCount('feed_quality', 100);
        $qualityFourStarReviewCount = $collection->getAllReviewCount('feed_quality', 80);
        $qualityThreeStarReviewCount = $collection->getAllReviewCount('feed_quality', 60);
        $qualityTwoStarReviewCount = $collection->getAllReviewCount('feed_quality', 40);
        $qualityOneStarReviewCount = $collection->getAllReviewCount('feed_quality', 20);
        $qualityFiveStarReview = 0;
        $qualityFourStarReview = 0;
        $qualityThreeStarReview = 0;
        $qualityTwoStarReview = 0;
        $qualityOneStarReview = 0;
        if (!empty($qualityFiveStarReviewCount[0])) {
            $qualityFiveStarReview = $qualityFiveStarReviewCount[0];
        }
        if (!empty($qualityFourStarReviewCount[0])) {
            $qualityFourStarReview = $qualityFourStarReviewCount[0];
        }
        if (!empty($qualityThreeStarReviewCount[0])) {
            $qualityThreeStarReview = $qualityThreeStarReviewCount[0];
        }
        if (!empty($qualityTwoStarReviewCount[0])) {
            $qualityTwoStarReview = $qualityTwoStarReviewCount[0];
        }
        if (!empty($qualityOneStarReviewCount[0])) {
            $qualityOneStarReview = $qualityOneStarReviewCount[0];
        }

        $data = [
            'price' => $price / $collectionCount,
            'value' => $value / $collectionCount,
            'quality' => $quality / $collectionCount,
            'totalfeed' => $totalfeed,
            'feedcount' => $feedCount,
            'price_star_5' => $priceFiveStarReview,
            'price_star_4' => $priceFourStarReview,
            'price_star_3' => $priceThreeStarReview,
            'price_star_2' => $priceTwoStarReview,
            'price_star_1' => $priceOneStarReview,
            'value_star_5' => $valueFiveStarReview,
            'value_star_4' => $valueFourStarReview,
            'value_star_3' => $valueThreeStarReview,
            'value_star_2' => $valueTwoStarReview,
            'value_star_1' => $valueOneStarReview,
            'quality_star_5' => $qualityFiveStarReview,
            'quality_star_4' => $qualityFourStarReview,
            'quality_star_3' => $qualityThreeStarReview,
            'quality_star_2' => $qualityTwoStarReview,
            'quality_star_1' => $qualityOneStarReview
        ];

        return $data;
    }

    public function getSelleRating($sellerId)
    {
        $feeds = $this->getFeedTotal($sellerId);
        $totalRating = (
            $feeds['price'] + $feeds['value'] + $feeds['quality']
        ) / 60;

        return round($totalRating, 1, PHP_ROUND_HALF_UP);
    }

    public function getCatatlogGridPerPageValues()
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/grid_per_page_values',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCaptchaEnable()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/captcha',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDefaultTransEmailId()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAdminEmailId()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/adminemail',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedCategoryIds()
    {
        $seller = $this->getSeller();
        if ($seller['allowed_categories']) {
            return $seller['allowed_categories'];
        } else {
            return $this->scopeConfig->getValue(
                'marketplace/product_settings/categoryids',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
    }

    public function getIsProductEditApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/product_edit_approval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsPartnerApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/seller_approval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsProductApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/product_approval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedAttributesetIds()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/attributesetid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedProductType()
    {
        $productTypes = $this->scopeConfig->getValue(
            'marketplace/product_settings/allow_for_seller',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $data = explode(',', $productTypes);
        foreach ($data as $key => $value) {
            if ($value == 'grouped') {
                if ($this->_moduleManager->isEnabled('Webkul_MpGroupedProduct')) {
                    $data['grouped'] = __('Grouped Product');
                } else {
                    unset($data[$key]);
                }
            }
            if ($value == 'bundle') {
                if ($this->_moduleManager->isEnabled('Webkul_MpBundleProduct')) {
                    $data['bundle'] = __('Bundle Product');
                } else {
                    unset($data[$key]);
                }
            }
        }
        return implode(',', $data);
    }

    public function getUseCommissionRule()
    {
        return $this->scopeConfig->getValue(
            'mpadvancedcommission/options/use_commission_rule',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCommissionType()
    {
        return $this->scopeConfig->getValue(
            'mpadvancedcommission/options/commission_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsOrderManage()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/order_manage',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigCommissionRate()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/percent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigTaxManage()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/tax_manage',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockNotification()
    {
        return $this->scopeConfig->getValue(
            'marketplace/inventory_settings/low_stock_notification',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockQty()
    {
        return $this->scopeConfig->getValue(
            'marketplace/inventory_settings/low_stock_amount',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getActiveColorPicker()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/activecolorpicker',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerPolicyApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/seller_policy_approval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getUrlRewrite()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/url_rewrite',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getReviewStatus()
    {
        return $this->scopeConfig->getValue(
            'marketplace/review_settings/review_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceHeadLabel()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel1',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel4()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel4',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBanner()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybanner',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImage()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/banner',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContent()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIcon()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displayicons',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel1()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel4()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacebutton()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebutton',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceprofile()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplaceprofile',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlisttopLabel()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/sellerlisttop',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlistbottomLabel()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/sellerlistbottom',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStatus()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_hint_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintCategory()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_category',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintName()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_des',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintShortDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sdes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSku()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sku',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintPrice()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_price',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSpecialPrice()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sprice',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStartDate()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sdate',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEndDate()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_edate',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintQty()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_qty',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStock()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_stock',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintTax()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_tax',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintWeight()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_weight',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintImage()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_image',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEnable()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintStatus()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_hint_status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBecomeSeller()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/become_seller',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShopurl()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/shopurl_seller',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintTw()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_tw',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintFb()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_fb',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCn()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_cn',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_bc',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShop()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_shop',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBanner()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_banner',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLogo()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLoc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_loc',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_desciption',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintReturnPolicy()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/returnpolicy',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShippingPolicy()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/shippingpolicy',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCountry()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_country',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMeta()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_meta',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMetaDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_mdesc',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBank()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_bank',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/profile/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getCollectionUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/collection/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getLocationUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/location/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getFeedbackUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/feedback/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getRewriteUrl($targetUrl)
    {
        $requestUrl = $this->_urlBuilder->getUrl(
            '',
            [
                '_direct' => $targetUrl,
                '_secure' => $this->_request->isSecure(),
            ]
        );
        $urlColl = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
            ->getCollection()
            ->addFieldToFilter('target_path', $targetUrl)
            ->addFieldToFilter('store_id', $this->getCurrentStoreId());
        foreach ($urlColl as $value) {
            $requestUrl = $this->_urlBuilder->getUrl(
                '',
                [
                    '_direct' => $value->getRequestPath(),
                    '_secure' => $this->_request->isSecure(),
                ]
            );
        }

        return $requestUrl;
    }

    public function getRewriteUrlPath($targetUrl)
    {
        $requestPath = '';
        $urlColl = $this->_objectManager->create(
            'Magento\UrlRewrite\Model\UrlRewrite'
        )
            ->getCollection()
            ->addFieldToFilter(
                'target_path',
                $targetUrl
            )
            ->addFieldToFilter(
                'store_id',
                $this->getCurrentStoreId()
            );
        foreach ($urlColl as $value) {
            $requestPath = $value->getRequestPath();
        }

        return $requestPath;
    }

    public function getTargetUrlPath()
    {
        $urls = explode(
            $this->_urlBuilder->getUrl(
                '',
                ['_secure' => $this->_request->isSecure()]
            ),
            $this->_urlBuilder->getCurrentUrl()
        );
        $targetUrl = '';
        $temp = explode('/?', $urls[1]);
        if (!isset($temp[1])) {
            $temp[1] = '';
        }
        if (!$temp[1]) {
            $temp = explode('?', $temp[0]);
        }
        $requestPath = $temp[0];
        $urlColl = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite')
            ->getCollection()
            ->addFieldToFilter(
                'request_path',
                ['eq' => $requestPath]
            )
            ->addFieldToFilter(
                'store_id',
                ['eq' => $this->getCurrentStoreId()]
            );
        foreach ($urlColl as $value) {
            $targetUrl = $value->getTargetPath();
        }

        return $targetUrl;
    }

    public function getPlaceholderImage()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/placeholder/image.jpg';
    }

    public function getSellerProCount($sellerId)
    {
        $querydata = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
            ->getCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED])
            ->addFieldToSelect('mageproduct_id')
            ->setOrder('mageproduct_id');
        $collection = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
            ->getCollection();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $collection->addAttributeToFilter('visibility', ['in' => [4]]);
        $collection->addAttributeToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED]);
        $collection->addStoreFilter();
        return $collection->getSize();
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
    }

    public function getMaxDownloads()
    {
        return $this->scopeConfig->getValue(
            \Magento\Downloadable\Model\Link::XML_PATH_DEFAULT_DOWNLOADS_NUMBER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigPriceWebsiteScope()
    {
        $scope = $this->scopeConfig->getValue(
            \Magento\Store\Model\Store::XML_PATH_PRICE_SCOPE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($scope == \Magento\Store\Model\Store::PRICE_SCOPE_WEBSITE) {
            return true;
        }

        return false;
    }

    public function getSkuType()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/sku_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSkuPrefix()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/sku_prefix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerProfileDisplayFlag()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/seller_profile_display',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAutomaticUrlRewrite()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/auto_url_rewrite',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve YouTube API key
     *
     * @return string
     */
    public function getYouTubeApiKey()
    {
        return $this->scopeConfig->getValue(
            'catalog/product_video/youtube_api_key'
        );
    }

    public function getAllowedControllersBySetData($allowedModule)
    {
        $allowedModuleArr=[];
        if ($allowedModule && $allowedModule!='all') {
            $allowedModuleControllers = explode(',', $allowedModule);
            foreach ($allowedModuleControllers as $key => $value) {
                array_push($allowedModuleArr, $value);
            }
        } else {
            $controllersRepository = $this->_objectManager->create(
                'Webkul\Marketplace\Model\ControllersRepository'
            );
            $controllersList = $controllersRepository->getList();
            foreach ($controllersList as $key => $value) {
                array_push($allowedModuleArr, $value['controller_path']);
            }
        }
        return $allowedModuleArr;
    }

    public function isSellerGroupModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerGroup')) {
            return true;
        }
        return false;
    }

    public function isAllowedAction($actionName = '')
    {
        if ($this->isSellerGroupModuleInstalled()) {
            $sellerGroupHelper = $this->_objectManager->create(
                'Webkul\MpSellerGroup\Helper\Data'
            );
            if (!$sellerGroupHelper->getStatus()) {
                return true;
            }
            $sellerId = $this->getCustomerId();
            $sellerGroupTypeRepository = $this->_objectManager->create(
                'Webkul\MpSellerGroup\Api\SellerGroupTypeRepositoryInterface'
            );
            if (!$sellerGroupTypeRepository->getBySellerCount($sellerId)) {
                $products = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Product'
                )->getCollection()
                ->addFieldToFilter(
                    'seller_id',
                    $this->getCustomerId()
                );
                $getDefaultGroupStatus = $sellerGroupHelper->getDefaultGroupStatus();
                if ($getDefaultGroupStatus) {
                    $allowqty = $sellerGroupHelper->getDefaultProductAllowed();
                    $allowFunctionalities = explode(',', $sellerGroupHelper->getDefaultAllowedFeatures());
                    if ($allowqty >= count($products)) {
                        if (in_array($actionName, $allowFunctionalities, true)) {
                            return true;
                        }
                    }
                }
            }
            $getSellerGroup = $sellerGroupTypeRepository->getBySellerId($sellerId);
            if (count($getSellerGroup->getData())) {
                $getSellerTypeGroup = $getSellerGroup;
                $allowedModuleArr = $this->getAllowedControllersBySetData(
                    $getSellerTypeGroup['allowed_modules_functionalities']
                );
                if (in_array($actionName, $allowedModuleArr, true)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function getPageLayout()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/pageLayout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBannerLayout2()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybannerLayout2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImageLayout2()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannerLayout2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContentLayout2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontentLayout2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerButtonLayout2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebuttonLayout2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getTermsConditionUrlLayout2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/termConditionLinkLayout2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    public function getDisplayBannerLayout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybannerLayout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImageLayout3()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannerLayout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContentLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontentLayout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerButtonLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebuttonLayout2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getTermsConditionUrlLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/termConditionLinkLayout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIconLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displayiconsLayout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel1Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_label_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel2Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_label_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel3Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_label_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel4Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_label_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage5Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon5_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel5Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon5_label_layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1Layout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel1Layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2Layout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel2Layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3Layout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel3Layout3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderApprovalRequired()
    {
        return $this->scopeConfig->getValue(
            'marketplace/order_settings/order_approval',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowProductLimit()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/allow_product_limit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getGlobalProductLimitQty()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/global_product_limit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderedPricebyorder($order, $price)
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        /*
        * Get Current Store Currency Rate
        */
        $currentCurrencyCode = $order->getOrderCurrencyCode();
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $allowedCurrencies = $helper->getConfigAllowCurrencies();
        $rates = $helper->getCurrencyRates(
            $baseCurrencyCode,
            array_values($allowedCurrencies)
        );
        if (empty($rates[$currentCurrencyCode])) {
            $rates[$currentCurrencyCode] = 1;
        }
        return $price / $rates[$currentCurrencyCode];
    }

    public function getCurrentCurrencyAmountbyorder($order, $price)
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        /*
        * Get Current Store Currency Rate
        */
        $currentCurrencyCode = $order->getOrderCurrencyCode();
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $allowedCurrencies = $helper->getConfigAllowCurrencies();
        $rates = $helper->getCurrencyRates(
            $baseCurrencyCode,
            array_values($allowedCurrencies)
        );
        if (empty($rates[$currentCurrencyCode])) {
            $rates[$currentCurrencyCode] = 1;
        }
        return $price * $rates[$currentCurrencyCode];
    }

    public function isSellerCouponModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerCoupons')) {
            return true;
        }
        return false;
    }

    public function getCustomerSharePerWebsite()
    {
        return $this->scopeConfig->getValue(
            'customer/account_share/scope',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isMpcashondeliveryModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_Mpcashondelivery')) {
            return true;
        }
        return false;
    }

    public function getFormatedPrice($price = 0)
    {
        $currency = $this->_localeCurrency->getCurrency(
            $this->getBaseCurrencyCode()
        );
        return $currency->toCurrency(sprintf("%f", $price));
    }

    public function getIsSeparatePanel()
    {
        return $this->scopeConfig->getValue(
            'marketplace/layout_settings/is_separate_panel',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsAdminViewCategoryTree()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/is_admin_view_category_tree',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare Permissions Mapping with controllers.
     *
     * @return array
     */
    public function getControllerMappedPermissions()
    {
        return [
            'marketplace/account/askquestion' => 'marketplace/account/dashboard',
            'marketplace/account_dashboard/tunnel' => 'marketplace/account/dashboard',
            'marketplace/account/chart' => 'marketplace/account/dashboard',
            'marketplace/account/becomesellerPost' => 'marketplace/account/becomeseller',
            'marketplace/account/deleteSellerBanner' => 'marketplace/account/editProfile',
            'marketplace/account/deleteSellerLogo' => 'marketplace/account/editProfile',
            'marketplace/account/editProfilePost' => 'marketplace/account/editProfile',
            'marketplace/account/rewriteUrlPost' => 'marketplace/account/editProfile',
            'marketplace/account/savePaymentInfo' => 'marketplace/account/editProfile'
        ];
    }

    public function isMpSellerProductSearchModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerProductSearch')) {
            return true;
        }
        return false;
    }
}
