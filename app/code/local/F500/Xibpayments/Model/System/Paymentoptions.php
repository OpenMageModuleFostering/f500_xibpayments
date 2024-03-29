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

class F500_Xibpayments_Model_System_Paymentoptions
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'', 'label'=>Mage::helper('xibpayments')->__('All')),
            array('value'=>'creditcard', 'label'=>Mage::helper('xibpayments')->__('creditcard')),
            array('value'=>'ideal', 'label'=>Mage::helper('xibpayments')->__('ideal')),
            array('value'=>'paypal', 'label'=>Mage::helper('xibpayments')->__('paypal')),
            array('value'=>'mistercash', 'label'=>Mage::helper('xibpayments')->__('mistercash')),
            array('value'=>'giropay', 'label'=>Mage::helper('xibpayments')->__('giropay')),
            array('value'=>'directebanking', 'label'=>Mage::helper('xibpayments')->__('directebanking')),
            array('value'=>'webmoney', 'label'=>Mage::helper('xibpayments')->__('webmoney')),            
        );
    }
}
