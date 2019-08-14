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
 
class F500_Xibpayments_SecureController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Valid callback IP's
	 */
	protected $valid_ips = array(
		'213.207.89.161',
		'213.207.89.162',
		'213.207.89.163',
		'213.207.89.164',
		'213.207.89.165',
		'213.207.89.166',
        '127.0.0.1'
	);
	
    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @param    none
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with Xibpayments standard order transaction information
     *
     * @return Mage_Xibpayments_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('xibpayments/standard');
    }

    /**
     * When a customer chooses Xibpayments on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setXibpaymentsStandardQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('xibpayments/redirect')->toHtml());
        $session->unsQuoteId();
    }

    /**
     * When a customer cancel payment from Xibpayments.
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('xibpayments/session');
        $session->setQuoteId($session->getXibpaymentsStandardQuoteId(true));

        // cancel order
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        //redirect to checkout one page
        $this->_redirect('checkout/cart');
    }

    /**
     * Customer returns from Xibpayments
     * The order information at this point is in POST variables.  
     */
    public function  successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getXibpaymentsStandardQuoteId(true));
        /**
         * set the quote as inactive after back from Xibpayments
         */
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }

    /**
     * Xibpayments Callback: control_url
     * cannot have any output here
     */
    public function controlAction()
    {
        /**
         *  Defend agains callbacks from illegal IP's?
         */
    
        if ( !$this->_request->isPost() || !in_array($this->_request->getServer('REMOTE_ADDR'),$this->valid_ips) ) {
        	
            throw new Zend_Exception('Improper use of xibpayments control url');
            exit(0);
        }

        $this->getStandard()->setCallbackData($this->getRequest()->getPost());
        $this->getStandard()->processCallback();
    }
}
