<?php

namespace CreativeRox\Setup\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Config\Model\ResourceModel\Config;

class InstallData implements InstallDataInterface
{
	protected $eavSetup;
	protected $_resourceConfig;

	public function __construct(
		\Magento\Eav\Setup\EavSetup $eavSetup,
        Config $resourceConfig
	)
	{
		$this->eavSetup = $eavSetup;
        $this->_resourceConfig = $resourceConfig;
	}

	/**
	 * Installs data for a module
	 *
	 * @param ModuleDataSetupInterface $setup
	 * @param ModuleContextInterface $context
	 * @return void
	 */
	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();

        

		$setup->endSetup();
	}
}