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
namespace Webkul\Marketplace\Ui\DataProvider;

use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection as OrderColl;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Class LatestOrdersDataProvider
 */
class LatestOrdersDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Collection for getting table name
     *
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    protected $orderColl;

    /**
     * Saleslist Orders collection
     *
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    protected $collection;

    /**
     * @var HelperData
     */
    public $helperData;

    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param OrderColl $orderColl
     * @param CollectionFactory $collectionFactory
     * @param HelperData $helperData
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        OrderColl $orderColl,
        CollectionFactory $collectionFactory,
        HelperData $helperData,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $sellerId = $helperData->getCustomerId();

        $marketplaceSaleslist = $orderColl->getTable('marketplace_saleslist');
        $salesOrder = $orderColl->getTable('sales_order');
        $salesOrderItem = $orderColl->getTable('sales_order_item');
        $customerGridFlat = $orderColl->getTable('customer_grid_flat');
        $collectionData = $collectionFactory->create();
        $collectionData->getSelect()->where('main_table.seller_id = '.$sellerId);
        $collectionData->getSelect()->join(
            $marketplaceSaleslist.' as ms',
            'main_table.order_id = ms.order_id AND main_table.seller_id = ms.seller_id',
            [
                "magerealorder_id" => "magerealorder_id",
                "magebuyer_id" => "magebuyer_id"
            ]
        )
        ->columns(
            [
                'SUM(actual_seller_amount) AS actual_seller_amount',
                'SUM(actual_seller_amount) AS purchased_actual_seller_amount',
                'SUM(applied_coupon_amount) AS applied_coupon_amount'
            ]
        )
        ->group('ms.order_id');
        $collectionData->getSelect()->join(
            $salesOrder.' as so',
            'main_table.order_id = so.entity_id',
            [
                "state" => "state",
                "status" => "status",
            ]
        );

        $collectionData->getSelect()->join(
            $customerGridFlat.' as cgf',
            'ms.magebuyer_id = cgf.entity_id',
            [
                'name' => 'name',
            ]
        );
        $collectionData->getSelect()->where(
            'so.order_approval_status = 1'
        );
        $collectionData->setOrder('main_table.order_id', 'DESC');
        $collectionData->getSelect()->limit(5);
        $this->collection = $collectionData;
    }
}
