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

namespace Webkul\Marketplace\Ui\Component\Listing\Columns\Frontend;

use Magento\Customer\Ui\Component\Listing\Columns;

/**
 * Class CustomerAttribute.
 */
class CustomerAttribute extends Columns
{
    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $this->columnSortOrder = $this->getDefaultSortOrder();
        foreach ($this->attributeRepository->getList() as $newAttributeCode => $attributeData) {
            if (isset($this->components[$newAttributeCode])) {
                $this->updateColumn($attributeData, $newAttributeCode);
            }
        }
        $this->updateActionColumnSortOrder();
    }
}
