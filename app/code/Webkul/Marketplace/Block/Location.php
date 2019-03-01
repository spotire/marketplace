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

namespace Webkul\Marketplace\Block;

/*
 * Webkul Marketplace Seller Location Block
 */
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;

class Location extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $session;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webkul\Marketplace\Helper\Data                  $helper
     * @param Customer                                         $customer
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Webkul\Marketplace\Helper\Data $helper,
        Customer $customer,
        \Magento\Customer\Model\Session $session,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->Customer = $customer;
        $this->Session = $session;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return array
     */
    public function getProfileDetail($value = '')
    {
        $shopUrl = $this->helper->getLocationUrl();
        if (!$shopUrl) {
            $shopUrl = $this->getRequest()->getParam('shop');
        }
        if ($shopUrl) {
            $data = $this->helper->getSellerCollectionObjByShop(
                $shopUrl
            );
            foreach ($data as $seller) {
                return $seller;
            }
        }
    }
}
