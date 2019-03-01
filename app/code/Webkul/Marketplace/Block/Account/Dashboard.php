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

namespace Webkul\Marketplace\Block\Account;

use Magento\Customer\Model\Customer;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\Session;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\CategoryRepository;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Orders as HelperOrders;

class Dashboard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\Order\ItemRepository
     */
    protected $orderItemRepository;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Customer               $customer
     * @param Session                $customerSession
     * @param CollectionFactory      $orderCollectionFactory
     * @param OrderRepository        $orderRepository
     * @param ProductRepository      $productRepository
     * @param CategoryRepository     $categoryRepository
     * @param Context                $context
     * @param array                  $data
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Customer $customer,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\ItemRepository $orderItemRepository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Context $context,
        array $data = []
    ) {
        $this->_customer = $customer;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Seller Dashboard'));
    }

    public function getCustomer()
    {
        return $this->_customer;
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getCustomerId();
    }

    /**
     * @return \Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    public function getCollection()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }

        $paramData = $this->getRequest()->getParams();
        $filterOrderid = '';
        $filterOrderstatus = '';
        $filterDataTo = '';
        $filterDataFrom = '';
        $from = null;
        $to = null;

        if (isset($paramData['s'])) {
            $filterOrderid = $paramData['s'] != '' ? $paramData['s'] : '';
        }
        if (isset($paramData['orderstatus'])) {
            $filterOrderstatus = $paramData['orderstatus'] != '' ? $paramData['orderstatus'] : '';
        }
        if (isset($paramData['from_date'])) {
            $filterDataFrom = $paramData['from_date'] != '' ? $paramData['from_date'] : '';
        }
        if (isset($paramData['to_date'])) {
            $filterDataTo = $paramData['to_date'] != '' ? $paramData['to_date'] : '';
        }

        $orderids = $this->getOrderIdsArray($customerId, $filterOrderstatus);

        $ids = $this->getEntityIdsArray($orderids);

        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'entity_id',
            ['in' => $ids]
        );

        if ($filterDataTo) {
            $todate = date_create($filterDataTo);
            $to = date_format($todate, 'Y-m-d 23:59:59');
        }
        if ($filterDataFrom) {
            $fromdate = date_create($filterDataFrom);
            $from = date_format($fromdate, 'Y-m-d H:i:s');
        }

        if ($filterOrderid) {
            $collection->addFieldToFilter(
                'magerealorder_id',
                ['eq' => $filterOrderid]
            );
        }

        $collection->addFieldToFilter(
            'created_at',
            ['datetime' => true, 'from' => $from, 'to' => $to]
        );

        $collection->setOrder(
            'created_at',
            'desc'
        );
        $collection->setPageSize(5);

        return $collection;
    }

    public function getOrderIdsArray($customerId = '', $filterOrderstatus = '')
    {
        $orderids = [];

        $collectionOrders = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $customerId]
        )
        ->addFieldToSelect('order_id')
        ->distinct(true);

        foreach ($collectionOrders as $collectionOrder) {
            $tracking = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Orders'
            )->getOrderinfo($collectionOrder->getOrderId());

            if ($tracking) {
                if ($filterOrderstatus) {
                    if ($tracking->getIsCanceled()) {
                        if ($filterOrderstatus == 'canceled') {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    } else {
                        $tracking = $this->orderRepository->get($collectionOrder->getOrderId());
                        if ($tracking->getStatus() == $filterOrderstatus) {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    }
                } else {
                    array_push($orderids, $collectionOrder->getOrderId());
                }
            }
        }

        return $orderids;
    }

    public function getEntityIdsArray($orderids = [])
    {
        $ids = [];
        foreach ($orderids as $orderid) {
            $collectionIds = $this->_orderCollectionFactory->create()
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderid]
            )
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(1);
            foreach ($collectionIds as $collectionId) {
                $autoid = $collectionId->getId();
                array_push($ids, $autoid);
            }
        }

        return $ids;
    }

    public function getDateDetail()
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection1 = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection2 = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection3 = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );

        $firstDayOfWeek = date('Y-m-d', strtotime('Last Monday', time()));

        $lastDayOfWeek = date('Y-m-d', strtotime('Next Sunday', time()));

        $month = $collection1->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m').'-01 00:00:00',
                'to' => date('Y-m').'-31 23:59:59',
            ]
        );

        $week = $collection2->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => $firstDayOfWeek.' 00:00:00',
                'to' => $lastDayOfWeek.' 23:59:59',
            ]
        );

        $day = $collection3->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m-d').' 00:00:00',
                'to' => date('Y-m-d').' 23:59:59',
            ]
        );

        $sale = 0;

        $data1['year'] = $sale;

        $sale1 = 0;
        foreach ($day as $record1) {
            $sale1 = $sale1 + $record1->getActualSellerAmount();
        }
        $data1['day'] = $sale1;

        $sale2 = 0;
        foreach ($month as $record2) {
            $sale2 = $sale2 + $record2->getActualSellerAmount();
        }
        $data1['month'] = $sale2;

        $sale3 = 0;
        foreach ($week as $record3) {
            $sale3 = $sale3 + $record3->getActualSellerAmount();
        }
        $data1['week'] = $sale3;

        $temp = 0;
        foreach ($collection as $record) {
            $temp = $temp + $record->getActualSellerAmount();
        }
        $data1['totalamount'] = $temp;

        return $data1;
    }

    public function getpronamebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        );
        $name = '';
        foreach ($collection as $res) {
            $item = $this->orderItemRepository->get($res['order_item_id']);
            $product = $item->getProduct();

            if ($product) {
                $name .= "<p style='float:left;'><a href='".
                $product->getProductUrl().
                "' target='blank'>".
                $res['magepro_name'].
                '</a> X '.
                intval($res['magequantity']).
                '&nbsp;</p>';
            } else {
                $name .= "<p style='float:left;'>".
                $res['magepro_name'].
                '</a> X '.
                intval($res['magequantity']).
                '&nbsp;</p>';
            }
        }

        return $name;
    }

    public function getPricebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->getPricebyorderData();
        $name = '';
        foreach ($collection as $coll) {
            if ($coll->getOrderId() == $orderId) {
                return $coll->getTotal();
            }
        }
    }

    public function getTopSaleProducts()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        )
        ->getAllOrderProducts();
        $name = '';
        $resultData = [];
        foreach ($collection as $coll) {
            $item = $this->orderItemRepository->get($coll['order_item_id']);
            $product = $item->getProduct();
            if ($product) {
                $resultData[$coll->getId()]['name'] = $product->getName();
                $resultData[$coll->getId()]['url'] = $product->getProductUrl();
                $resultData[$coll->getId()]['qty'] = $coll['qty'];
            } else {
                $resultData[$coll->getId()]['name'] = $item->getName();
                $resultData[$coll->getId()]['url'] = '';
                $resultData[$coll->getId()]['qty'] = $coll['qty'];
            }
        }
        return $resultData;
    }

    public function getTopSaleCategories()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        )
        ->getAllOrderProducts();
        $name = '';
        $resultData = [];
        $catArr = [];
        $totalOrderedProducts = 0;
        foreach ($collection as $coll) {
            $totalOrderedProducts = $totalOrderedProducts + $coll['qty'];
        }
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        );
        foreach ($collection as $coll) {
            $item = $this->orderItemRepository->get($coll['order_item_id']);
            $product = $item->getProduct();
            if ($product) {
                $productCategories = $product->getCategoryIds();
                if (isset($productCategories[0])) {
                    if (!isset($catArr[$productCategories[0]])) {
                        $catArr[$productCategories[0]] = $coll['magequantity'];
                    } else {
                        $catArr[$productCategories[0]] = $catArr[$productCategories[0]] + $coll['magequantity'];
                    }
                }
            }
        }
        $categoryArr = [];
        $percentageArr = [];
        foreach ($catArr as $key => $value) {
            $percentageArr[$key] = round((($value * 100) / $totalOrderedProducts), 2);
            $categoryArr[$key] = $this->categoryRepository->get($key)->getName();
        }
        $resultData['percentage_arr'] = $percentageArr;
        $resultData['category_arr'] = $categoryArr;
        return $resultData;
    }

    public function getTotalSaleColl($value = '')
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleperpartner'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        );

        return $collection;
    }

    /**
     * Give the current url of recently viewed page.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    public function getReviewcollection($value = '')
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Feedback'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'status',
            ['eq' => 1]
        )
        ->setOrder(
            'created_at',
            'desc'
        )
        ->setPageSize(5)
        ->setCurPage(1);

        return $collection;
    }

    public function getMainOrder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Magento\Sales\Model\Order'
        )->getCollection()
        ->addFieldToFilter(
            'entity_id',
            ['eq' => $orderId]
        );
        foreach ($collection as $res) {
            return $res;
        }

        return [];
    }

    public function getOrderedPricebyorder($order, $basePrice)
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
        return $basePrice * $rates[$currentCurrencyCode];
    }

    public function getTotalOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        );
        return count($collection);
    }

    public function getPendingOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->getTotalOrders()
        ->getSellerOrderCollection();
        $collection->addFieldToFilter(
            'status',
            'pending'
        );
        return count($collection);
    }

    public function getProcessingOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->getTotalOrders()
        ->getSellerOrderCollection();
        $collection->addFieldToFilter(
            'status',
            'processing'
        );
        return count($collection);
    }

    public function getCompletedOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->getTotalOrders()
        ->getSellerOrderCollection();
        $collection->addFieldToFilter(
            'status',
            'complete'
        );
        return count($collection);
    }

    public function getTotalProducts()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        );
        return count($collection);
    }

    public function getTotalCustomers()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->getTotalCustomersCount();
        return count($collection);
    }

    public function getTotalCustomersCurrentMonth()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m').'-01 00:00:00',
                'to' => date('Y-m').'-31 23:59:59',
            ]
        )->getTotalCustomersCount();
        return count($collection);
    }

    public function getTotalCustomersLastMonth()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_orderCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m', strtotime('last month')).'-01 00:00:00',
                'to' => date('Y-m', strtotime('last month')).'-31 23:59:59',
            ]
        )->getTotalCustomersCount();
        return count($collection);
    }
}
