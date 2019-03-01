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

namespace Webkul\Marketplace\Controller\Product;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

/**
 * Webkul Marketplace Product Save Controller.
 */
class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var SaveProduct
     */
    protected $_saveProduct;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_productResourceModel;

    /**
     * @param Context          $context
     * @param Session          $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param SaveProduct      $saveProduct
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        SaveProduct $saveProduct,
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_saveProduct = $saveProduct;
        $this->_productResourceModel = $productResourceModel;
        parent::__construct(
            $context
        );
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * seller product save action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $returnArr = [];
                $productId = '';
                if ($this->getRequest()->isPost()) {
                    if (!$this->_formKeyValidator->validate($this->getRequest())) {
                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/create',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }

                    $wholedata = $this->getRequest()->getParams();

                    $skuType = $helper->getSkuType();
                    $skuPrefix = $helper->getSkuPrefix();
                    if ($skuType == 'dynamic') {
                        $sku = $skuPrefix.$wholedata['product']['name'];
                        $wholedata['product']['sku'] = $this->checkSkuExist($sku);
                    }
                    list($errors) = $this->validatePost($wholedata);

                    if (empty($errors)) {
                        $returnArr = $this->_saveProduct->saveProductData(
                            $this->_getSession()->getCustomerId(),
                            $wholedata
                        );
                        $productId = $returnArr['product_id'];
                    } else {
                        foreach ($errors as $message) {
                            $this->messageManager->addError($message);
                        }
                    }
                }
                if ($productId != '') {
                    if (empty($errors)) {
                        $this->messageManager->addSuccess(
                            __('Your product has been successfully saved')
                        );
                    }

                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [
                            'id' => $productId,
                            '_secure' => $this->getRequest()->isSecure(),
                        ]
                    );
                } else {
                    if (isset($returnArr['error']) && isset($returnArr['message'])) {
                        if ($returnArr['error'] && $returnArr['message'] != '') {
                            $this->messageManager->addError($returnArr['message']);
                        }
                    }

                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/create',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/create',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/create',
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

    private function checkSkuExist($sku)
    {
        try {
            $id = $this->_productResourceModel->getIdBySku($sku);
            if ($id) {
                $avialability = 0;
            } else {
                $avialability = 1;
            }
        } catch (\Exception $e) {
            $avialability = 0;
        }
        if ($avialability == 0) {
            $sku = $sku.rand();
            $sku = $this->checkSkuExist($sku);
        }
        return $sku;
    }
    /**
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validatePost(&$wholedata)
    {
        $errors = [];
        $data = [];
        foreach ($wholedata['product'] as $code => $value) {
            switch ($code) :
                case 'name':
                    $result = $this->nameValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Name has to be completed');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'description':
                    $result = $this->descriptionValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Description has to be completed');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'short_description':
                    $result = $this->descriptionValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $wholedata['product'][$code] = '';
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'price':
                    $result = $this->priceValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Price should contain only decimal numbers');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'weight':
                    $result = $this->weightValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Weight should contain only decimal numbers');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'stock':
                    $result = $this->stockValidateFunction($value, $code, $errors, $data);
                    if ($result['error']) {
                        $errors[] = __('Product quantity should contain only decimal numbers');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'sku_type':
                    $result = $this->skuTypeValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Sku Type has to be selected');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'sku':
                    $result = $this->skuValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Sku has to be completed');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'price_type':
                    $result = $this->priceTypeValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Price Type has to be selected');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'weight_type':
                    $result = $this->weightTypeValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Weight Type has to be selected');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'bundle_options':
                    $result = $this->bundleOptionValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Default Title has to be completed');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'meta_title':
                    $result = $this->metaTitleValidateFunction($value, $code, $data);
                    $wholedata['product'][$code] = $result['data'][$code];
                    break;
                case 'meta_keyword':
                    $result = $this->metaKeywordValidateFunction($value, $code, $data);
                    $wholedata['product'][$code] = $result['data'][$code];
                    break;
                case 'meta_description':
                    $result = $this->metaDiscValidateFunction($value, $code, $data);
                    $wholedata['product'][$code] = $result['data'][$code];
                    break;
                case 'mp_product_cart_limit':
                    $result = $this->stockValidateFunction($value, $code, $errors, $data);
                    if ($result['error']) {
                        $errors[] = __('Allowed Product Qty should contain only decimal numbers');
                    } else {
                        $wholedata['product'][$code] = $result['data'][$code];
                    }
                    break;
            endswitch;
        }

        return [$errors];
    }

    private function nameValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }

    private function descriptionValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ? : $value;
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function shortDescValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function priceValidateFunction($value, $code, $data)
    {
        $error = false;
        if (!preg_match('/^([0-9])+?[0-9.,]*$/', $value)) {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function weightValidateFunction($value, $code, $data)
    {
        $error = false;
        if (!preg_match('/^([0-9])+?[0-9.,]*$/', $value)) {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function stockValidateFunction($value, $code, $data)
    {
        $error = false;
        if (!preg_match('/^([0-9])+?[0-9.]*$/', $value)) {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function skuTypeValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function skuValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }

    private function priceTypeValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function weightTypeValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    private function bundleOptionValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }
    private function metaTitleValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }
    private function metaKeywordValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ? : $value;
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }
    private function metaDiscValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ? : $value;
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }
}
