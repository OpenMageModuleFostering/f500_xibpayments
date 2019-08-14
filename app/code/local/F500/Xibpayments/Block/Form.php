<?php
/**
 * Magento Xib|payments Payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   F500
 * @package    F500_Xibpayments
 * @author     Ramon de la Fuente, <ramon@future500.nl>
 * @copyright  Copyright (c) 2009 Future500 BV, the Netherlands
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class F500_Xibpayments_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('xibpayments/form.phtml');
        parent::_construct();
    }
}