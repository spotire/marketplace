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

namespace Webkul\Marketplace\Controller\Order;

/**
 * Webkul Marketplace Order Invoice Controller.
 */
class Invoice extends \Webkul\Marketplace\Controller\Order
{
    /**
     * Marketplace order invoice controller.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            if ($order = $this->_initOrder()) {
                $this->doInvoiceExecution($order);

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/view',
                    [
                        'id' => $order->getEntityId(),
                        '_secure' => $this->getRequest()->isSecure(),
                    ]
                );
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/history',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }

    protected function doInvoiceExecution($order)
    {
        try {
            $helper = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Data'
            );
            $sellerId = $this->_customerSession->getCustomerId();
            $orderId = $order->getId();
            if ($order->canUnhold()) {
                $this->messageManager->addError(
                    __('Can not create invoice as order is in HOLD state')
                );
            } else {
                $data = [];
                $data['send_email'] = 1;
                $marketplaceOrder = $this->_objectManager->create(
                    'Webkul\Marketplace\Helper\Orders'
                )->getOrderinfo($orderId);
                $invoiceId = $marketplaceOrder->getInvoiceId();
                if (!$invoiceId) {
                    $items = [];
                    $itemsarray = [];
                    $shippingAmount = 0;
                    $couponAmount = 0;
                    $codcharges = 0;
                    $paymentCode = '';
                    $paymentMethod = '';
                    if ($order->getPayment()) {
                        $paymentCode = $order->getPayment()->getMethod();
                    }
                    $trackingsdata = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Orders'
                    )
                    ->getCollection()
                    ->addFieldToFilter(
                        'order_id',
                        $orderId
                    )
                    ->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    );
                    foreach ($trackingsdata as $tracking) {
                        $shippingAmount = $tracking->getShippingCharges();
                        $couponAmount = $tracking->getCouponAmount();
                        if ($paymentCode == 'mpcashondelivery') {
                            $codcharges = $tracking->getCodCharges();
                        }
                    }
                    $codCharges = 0;
                    $tax = 0;
                    $collection = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Saleslist'
                    )
                    ->getCollection()
                    ->addFieldToFilter(
                        'order_id',
                        ['eq' => $orderId]
                    )
                    ->addFieldToFilter(
                        'seller_id',
                        ['eq' => $sellerId]
                    );
                    foreach ($collection as $saleproduct) {
                        if ($paymentCode == 'mpcashondelivery') {
                            $codCharges = $codCharges + $saleproduct->getCodCharges();
                        }
                        $tax = $tax + $saleproduct->getTotalTax();
                        array_push($items, $saleproduct['order_item_id']);
                    }

                    $itemsarray = $this->_getItemQtys($order, $items);

                    if (count($itemsarray) > 0 && $order->canInvoice()) {
                        $invoice = $this->_objectManager->create(
                            'Magento\Sales\Model\Service\InvoiceService'
                        )->prepareInvoice($order, $itemsarray['data']);
                        if (!$invoice) {
                            throw new \Magento\Framework\Exception\LocalizedException(
                                __('We can\'t save the invoice right now.')
                            );
                        }
                        if (!$invoice->getTotalQty()) {
                            throw new \Magento\Framework\Exception\LocalizedException(
                                __('You can\'t create an invoice without products.')
                            );
                        }
                        $this->_coreRegistry->register(
                            'current_invoice',
                            $invoice
                        );

                        if (!empty($data['capture_case'])) {
                            $invoice->setRequestedCaptureCase(
                                $data['capture_case']
                            );
                        }

                        if (!empty($data['comment_text'])) {
                            $invoice->addComment(
                                $data['comment_text'],
                                isset($data['comment_customer_notify']),
                                isset($data['is_visible_on_front'])
                            );

                            $invoice->setCustomerNote($data['comment_text']);
                            $invoice->setCustomerNoteNotify(
                                isset($data['comment_customer_notify'])
                            );
                        }
                        
                        $currentCouponAmount = $helper->getCurrentCurrencyAmountbyorder(
                            $order,
                            $couponAmount
                        );
                        $currentShippingAmount = $helper->getCurrentCurrencyAmountbyorder(
                            $order,
                            $shippingAmount
                        );
                        $currentTaxAmount = $helper->getCurrentCurrencyAmountbyorder(
                            $order,
                            $tax
                        );
                        $currentCodcharges = $helper->getCurrentCurrencyAmountbyorder(
                            $order,
                            $codcharges
                        );

                        $invoice->setBaseDiscountAmount($couponAmount);
                        $invoice->setDiscountAmount($currentCouponAmount);
                        $invoice->setShippingAmount($currentShippingAmount);
                        $invoice->setBaseShippingInclTax($shippingAmount);
                        $invoice->setBaseShippingAmount($shippingAmount);
                        $invoice->setSubtotal($itemsarray['subtotal']);
                        $invoice->setBaseSubtotal($itemsarray['baseSubtotal']);
                        if ($paymentCode == 'mpcashondelivery') {
                            $invoice->setMpcashondelivery($currentCodcharges);
                            $invoice->setBaseMpcashondelivery($codCharges);
                        }
                        $invoice->setGrandTotal(
                            $itemsarray['subtotal'] +
                            $currentShippingAmount +
                            $currentCodcharges +
                            $currentTaxAmount -
                            $currentCouponAmount
                        );
                        $invoice->setBaseGrandTotal(
                            $itemsarray['baseSubtotal'] +
                            $shippingAmount +
                            $codcharges +
                            $tax -
                            $couponAmount
                        );

                        $invoice->register();

                        $invoice->getOrder()->setCustomerNoteNotify(
                            !empty($data['send_email'])
                        );
                        $invoice->getOrder()->setIsInProcess(true);

                        $transactionSave = $this->_objectManager->create(
                            'Magento\Framework\DB\Transaction'
                        )->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transactionSave->save();

                        $invoiceId = $invoice->getId();

                        $this->_invoiceSender->send($invoice);

                        $this->messageManager->addSuccess(
                            __('Invoice has been created for this order.')
                        );
                    }
                    /*update mpcod table records*/
                    if ($invoiceId != '') {
                        if ($paymentCode == 'mpcashondelivery') {
                            $saleslistColl = $this->_objectManager->create(
                                'Webkul\Marketplace\Model\Saleslist'
                            )
                            ->getCollection()
                            ->addFieldToFilter(
                                'order_id',
                                $orderId
                            )
                            ->addFieldToFilter(
                                'seller_id',
                                $sellerId
                            );
                            foreach ($saleslistColl as $saleslist) {
                                $saleslist->setCollectCodStatus(1);
                                $saleslist->save();
                            }
                        }

                        $trackingcol1 = $this->_objectManager->create(
                            'Webkul\Marketplace\Model\Orders'
                        )
                        ->getCollection()
                        ->addFieldToFilter(
                            'order_id',
                            $orderId
                        )
                        ->addFieldToFilter(
                            'seller_id',
                            $sellerId
                        );
                        foreach ($trackingcol1 as $row) {
                            $row->setInvoiceId($invoiceId);
                            $row->save();
                        }
                    }
                } else {
                    $this->messageManager->addError(
                        __('Cannot create Invoice for this order.')
                    );
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('We can\'t save the invoice right now.')
            );
            $this->messageManager->addError($e->getMessage());
        }
    }
}
