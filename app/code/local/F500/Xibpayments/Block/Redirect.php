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

class F500_Xibpayments_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = Mage::getModel('xibpayments/standard');

        $form = new Varien_Data_Form();
        $form->setAction($standard->getXibpaymentsUrl())
            ->setId('xibpayments_checkout')
            ->setName('xibpayments_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->getCheckoutFormFields() as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to Xib|payments, our payment processor.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("xibpayments_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}