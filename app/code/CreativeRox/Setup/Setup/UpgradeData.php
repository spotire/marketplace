<?php

namespace CreativeRox\Setup\Setup;

use Magento\Catalog\Model\Category;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    protected $cmsPageFactory;
    protected $configResource;
    protected $eavSetupFactory;
    protected $attrUpdate;
    protected $products;
    protected $blockFactory;

    public function __construct(
        \Magento\Cms\Model\PageFactory $cmsPageFactory,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Action $attrUpdate,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $products,
        \Magento\Cms\Model\BlockFactory $blockFactory
    ) {
        $this->cmsPageFactory = $cmsPageFactory;
        $this->configResource = $configResource;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attrUpdate = $attrUpdate;
        $this->products = $products;
        $this->blockFactory = $blockFactory;
    }

    public function upgrade(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

		if (version_compare($context->getVersion(), "1.0.2") < 0) {

            /**
			 * Update content of b2b footer content CMS block.
			 */
			$contactPage = $this->cmsPageFactory->create()->load('contact-us', 'identifier');
			if ($contactPage->getId()) {


                $contactPage->setData('content', '<style>
    #mc_embed_signup {
        width: 50%!important;
        margin: 0 auto!important;
    }
    #mc_embed_signup .mc-field-group.input-group ul li {
        float: left!important;
        width: 50%!important;
    }
    #mc_embed_signup .mc-field-group label {
        width: 100%!important;
    }
</style>

<!-- Begin MailChimp Signup Form -->
<link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
<style type="text/css">
    #mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
    /* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
       We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
</style>
<div id="mc_embed_signup">
    <form action="//creativerox.us10.list-manage.com/subscribe/post?u=80e2df4a131eeb4bafda52215&amp;id=71a3467609" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
        <div id="mc_embed_signup_scroll">

            <div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
            <div class="mc-field-group">
                <label for="mce-MMERGE5">Title </label>
                <select name="MMERGE5" class="" id="mce-MMERGE5">
                    <option value=""></option>
                    <option value="Mr">Mr</option>
                    <option value="Mrs">Mrs</option>
                    <option value="Miss">Miss</option>
                    <option value="Ms">Ms</option>

                </select>
            </div>
            <div class="mc-field-group">
                <label for="mce-FNAME">First Name  <span class="asterisk">*</span>
                </label>
                <input type="text" value="" name="FNAME" class="required" id="mce-FNAME">
            </div>
            <div class="mc-field-group">
                <label for="mce-LNAME">Last Name  <span class="asterisk">*</span>
                </label>
                <input type="text" value="" name="LNAME" class="required" id="mce-LNAME">
            </div>
            <div class="mc-field-group">
                <label for="mce-MMERGE7">Company </label>
                <input type="text" value="" name="MMERGE7" class="" id="mce-MMERGE7">
            </div>
            <div class="mc-field-group">
                <label for="mce-MMERGE8">Country </label>
                <input type="text" value="" name="MMERGE8" class="" id="mce-MMERGE8">
            </div>
            <div class="mc-field-group size1of2">
                <label for="mce-MMERGE6">Phone Number  <span class="asterisk">*</span>
                </label>
                <input type="text" name="MMERGE6" class="required" value="" id="mce-MMERGE6">
            </div>
            <div class="mc-field-group">
                <label for="mce-EMAIL">Email Address  <span class="asterisk">*</span>
                </label>
                <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
            </div>
            <div class="mc-field-group size1of2">
                <label for="mce-DOB-month">Birthday </label>
                <div class="datefield">
                    <span class="subfield dayfield"><input class="datepart " type="text" pattern="[0-9]*" value="" placeholder="DD" size="2" maxlength="2" name="DOB[day]" id="mce-DOB-day"></span> /
                    <span class="subfield monthfield"><input class="datepart " type="text" pattern="[0-9]*" value="" placeholder="MM" size="2" maxlength="2" name="DOB[month]" id="mce-DOB-month"></span>
                    <span class="small-meta nowrap">( dd / mm )</span>
                </div>
            </div><div class="mc-field-group">
            <label for="mce-MMERGE3">Subject  <span class="asterisk">*</span>
            </label>
            <select name="MMERGE3" class="required" id="mce-MMERGE3">
                <option value=""></option>
                <option value="Customer Service">Customer Service</option>
                <option value="General Enquiry">General Enquiry</option>
                <option value="Product Enquiry">Product Enquiry</option>
                <option value="Wholesale Enquiry">Wholesale Enquiry</option>
                <option value="Feedback">Feedback</option>

            </select>
        </div>
            <div class="mc-field-group">
                <label for="mce-MMERGE9">Message  <span class="asterisk">*</span>
                </label>
                <input type="text" value="" name="MMERGE9" class="required" id="mce-MMERGE9">
            </div>
            <div class="mc-field-group input-group">
                <strong>Craft </strong>
                <ul><li><input type="checkbox" value="1" name="group[1][1]" id="mce-group[1]-1-0"><label for="mce-group[1]-1-0">Papercraft</label></li>
                    <li><input type="checkbox" value="2" name="group[1][2]" id="mce-group[1]-1-1"><label for="mce-group[1]-1-1">Card Making</label></li>
                    <li><input type="checkbox" value="4" name="group[1][4]" id="mce-group[1]-1-2"><label for="mce-group[1]-1-2">Scrapbooking</label></li>
                    <li><input type="checkbox" value="8" name="group[1][8]" id="mce-group[1]-1-3"><label for="mce-group[1]-1-3">Stamping</label></li>
                    <li><input type="checkbox" value="16" name="group[1][16]" id="mce-group[1]-1-4"><label for="mce-group[1]-1-4">Quilling</label></li>
                    <li><input type="checkbox" value="32" name="group[1][32]" id="mce-group[1]-1-5"><label for="mce-group[1]-1-5">Applique</label></li>
                    <li><input type="checkbox" value="64" name="group[1][64]" id="mce-group[1]-1-6"><label for="mce-group[1]-1-6">Quilting</label></li>
                    <li><input type="checkbox" value="128" name="group[1][128]" id="mce-group[1]-1-7"><label for="mce-group[1]-1-7">Upcycling</label></li>
                    <li><input type="checkbox" value="256" name="group[1][256]" id="mce-group[1]-1-8"><label for="mce-group[1]-1-8">Home Decor</label></li>
                    <li><input type="checkbox" value="512" name="group[1][512]" id="mce-group[1]-1-9"><label for="mce-group[1]-1-9">Knitting</label></li>
                    <li><input type="checkbox" value="1024" name="group[1][1024]" id="mce-group[1]-1-10"><label for="mce-group[1]-1-10">Sewing</label></li>
                    <li><input type="checkbox" value="2048" name="group[1][2048]" id="mce-group[1]-1-11"><label for="mce-group[1]-1-11">Cross Stitch</label></li>
                    <li><input type="checkbox" value="4096" name="group[1][4096]" id="mce-group[1]-1-12"><label for="mce-group[1]-1-12">Crochet</label></li>
                    <li><input type="checkbox" value="8192" name="group[1][8192]" id="mce-group[1]-1-13"><label for="mce-group[1]-1-13">Baking</label></li>
                </ul>
            </div>
            <div id="mce-responses" class="clear">
                <div class="response" id="mce-error-response" style="display:none"></div>
                <div class="response" id="mce-success-response" style="display:none"></div>
            </div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
            <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_80e2df4a131eeb4bafda52215_71a3467609" tabindex="-1" value=""></div>
            <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
        </div>
    </form>
</div>
<script type=\'text/javascript\' src=\'//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js\'></script>
<script type=\'text/javascript\'>(function($) {
    window.fnames = new Array();
    window.ftypes = new Array();
    fnames[5] = \'MMERGE5\';
    ftypes[5] = \'dropdown\';
    fnames[1] = \'FNAME\';
    ftypes[1] = \'text\';
    fnames[2] = \'LNAME\';
    ftypes[2] = \'text\';
    fnames[7] = \'MMERGE7\';
    ftypes[7] = \'text\';
    fnames[8] = \'MMERGE8\';
    ftypes[8] = \'text\';
    fnames[6] = \'MMERGE6\';
    ftypes[6] = \'phone\';
    fnames[0] = \'EMAIL\';
    ftypes[0] = \'email\';
    fnames[4] = \'DOB\';
    ftypes[4] = \'birthday\';
    fnames[3] = \'MMERGE3\';
    ftypes[3] = \'dropdown\';
    fnames[9] = \'MMERGE9\';
    ftypes[9] = \'text\';

}
(jQuery));

var $mcj = jQuery;

</script>
<!--End mc_embed_signup-->');
                $contactPage->save();

			}
		}

		/*
		 * Enabling paypal express billing agreement settings to allow paypal method appear on checkout payment methods list.
		 * known issue: https://github.com/magento/magento2/issues/10499
		 */
        if (version_compare($context->getVersion(), '1.0.3')  < 0) {
            $this->configResource->saveConfig('payment/paypal_billing_agreement/active', 1, 'default', 0);
        }



        if (version_compare($context->getVersion(), '1.0.11')  < 0) {
            $this->configResource->saveConfig('multishipping/options/checkout_multiple', 0, 'default', 0);
        }

        $setup->endSetup();
    }

}
