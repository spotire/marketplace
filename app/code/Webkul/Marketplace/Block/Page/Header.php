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
namespace Webkul\Marketplace\Block\Page;

class Header extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Webkul_Marketplace::layout2/page/header.phtml';

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webkul\Marketplace\Helper\Data                  $helper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Webkul\Marketplace\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getSellerShopName()
    {
        $sellerId = $this->helper->getCustomerId();
        $collection = $this->helper->getSellerCollectionObj($sellerId);
        $shopName = '';
        foreach ($collection as $key => $value) {
            $shopName = $value->getShopTitle();
            if (empty($value->getShopTitle())) {
                $shopName = $value->getShopUrl();
            }
        }
        return $shopName;
    }
    
    /**
     * @return string
     */
    public function getSellerLogo()
    {
        $sellerId = $this->helper->getCustomerId();
        $collection = $this->helper->getSellerCollectionObj($sellerId);
        $logoPic = 'noimage.png';
        foreach ($collection as $key => $value) {
            $logoPic = $value->getLogoPic();
            if (empty($logoPic)) {
                $logoPic = 'noimage.png';
            }
        }
        return $logoPic;
    }
}
