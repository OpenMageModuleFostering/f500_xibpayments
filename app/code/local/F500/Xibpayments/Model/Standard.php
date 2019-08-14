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

class F500_Xibpayments_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'xibpayments';
    protected $_formBlockType = 'xibpayments/form';
    protected $_allowCurrencyCode = array('EUR','USD');

    /**
     * Local testing or Live URL
     */
//    protected $_url='http://cardgateplus.loc/secure/';
    protected $_url='https://gateway.cardgateplus.com/';

    /**
     * Availability options
     */
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;


     /**
     * Get xibpayments session namespace
     *
     * @return F500_Xibayments_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('xibpayments/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /* validate the currency code is avaialable to use for xibpayments or not */
    public function validate()
    {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
            $this->debugLog('Unacceptable currency code ('.$currency_code.').');
            Mage::throwException(Mage::helper('xibpayments')->__('Selected currency code ') . $currency_code . Mage::helper('xibpayments')->__(' is not compatible with Xib|payments'));
        }
        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getUrl('xibpayments/secure/redirect', array('_secure' => true));
    }
    
    public function isTest() 
    {
        if ( $this->getConfigData('transaction_type') == 'T' ) {
            return true;
        }    
        return false;
    }

    public function isDebug() 
    {
        if ( $this->getConfigData('transaction_type') == 'D' ) {
            return true;
        }    
        return false;
    }
    
    public function debugLog($msg) {
        if ( $this->isDebug() ) {
            Mage::log($msg,null,$this->getConfigData('debug_log'));
        }
    }

    public function getCheckoutFormFields()
    {
		
		if ($this->getQuote()->getIsVirtual()) {
            $a = $this->getQuote()->getBillingAddress();
            $b = $this->getQuote()->getShippingAddress();
        } else {
            $a = $this->getQuote()->getShippingAddress();
            $b = $this->getQuote()->getBillingAddress();
        }

        $info = $this->getQuote()->getPayment();
        $option = $info->getData('xibpayments_option');
        switch ( $option ) {
        case 'ideal':
            $suboption = $info->getData('xibpayments_bank');
            break;
        default:
            $suboption = '';
            break;
        }
        
        //check if site_id and control_url are not empty
        //getQuoteCurrencyCode
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        $sArr = array(
            'siteid'            => $this->getConfigData('site_id'),
            'ref'               => $this->getCheckout()->getLastRealOrderId(),
            'first_name'        => $a->getFirstname(),
            'last_name'         => $a->getLastname(),
            'email'             => $a->getQuote()->getCustomer()->getEmail(),
            'address'           => $a->getStreet(1).( $a->getStreet(2) ? ', '.$a->getStreet(2) : ''),
            'city'              => $a->getCity(),
            'country_code'      => $a->getCountry(),
            'postal_code'       => $a->getPostcode(),
            'phone_number'      => $a->getTelephone(),
            'option'            => $option,
            'suboption'         => $suboption,
            'currency'          => $currency_code
        );

        if ( $this->isTest() || $this->isDebug() ) {
            $sArr = array_merge($sArr, array(
                    'test'      => '1'
                ));

            if ( $this->isDebug() ) {
                $sArr = array_merge($sArr, array(
                    'debug'      => '1'
                ));
            }
        }

		$amount = ($a->getBaseSubtotal()+$b->getBaseSubtotal())-($a->getBaseDiscountAmount()+$b->getBaseDiscountAmount());
		$grandTotalCents = sprintf('%.0f', $a->getGrandTotal()*100);
		$sArr = array_merge($sArr, array(
                'description'   => str_replace('%id%',$this->getCheckout()->getLastRealOrderId(),$this->getConfigData('order_description')),
				'amount'        => $grandTotalCents,
				'hash'		    => md5($this->getConfigData('site_id') . $grandTotalCents . $sArr['ref'] . $this->getConfigData('password_key'))
			));
		

        $rArr = array();
        foreach ($sArr as $k=>$v) {
            /*
            qreplacing & char with and. otherwise it will break the post
            */
            $value =  str_replace("&","and",$v);
            $rArr[$k] =  $value;
        }

        $this->debugLog('Sending customer to Xib|payments with values:');
        $this->debugLog('URL = ' . $this->getXibpaymentsUrl() );
        $this->debugLog($rArr);

        return $rArr;
    }

    public function getXibpaymentsUrl()
    {
        return $this->_url;
    }


    public function processCallback()
    {	    
        $id = $this->getCallbackData('ref');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($id);

        $this->debugLog($this->getCallbackData());

        /**
         *   200 Transaction successful
         *   300 Transaction failed
         *   301 Transaction failed by fraud check
         */

        if (!$order->getId()) {
            /**
             * need to have logic when there is no order with the order id from xibpayments?
             * probably a Mage::Exception?
             */
            $this->debugLog('No OrderID found with ID: ' . $id);

        } else {
                
            $amountInCents = (string) ($order->getBaseGrandTotal() * 100);
            $callbackAmount = (string) ($this->getCallbackData('amount'));

            if ( $amountInCents != $callbackAmount ) {

                $this->debugLog('Order amounts do not match. Sent ' . $amountInCents . ', Received: ' . $callbackAmount);

                $order->addStatusToHistory(
                    $order->getStatus(),
                    Mage::helper('xibpayments')->__('Order total amount does not match Xib|payments gross total amount')
                );
                $order->save();

            } else {
                if ($this->getCallbackData('status') == '200') { //success
                
                    if (!$order->canInvoice()) {
                        $this->debugLog('Unable to create invoice');

                        //when order cannot create invoice, need to have some logic to take care
                        $order->addStatusToHistory(
                            $order->getStatus(), // keep order status/state
                            Mage::helper('xibpayments')->__('Error in creating an invoice', true),
                            false
                        );
                    } else {
                        if ( Mage::getStoreConfig('payment/xibpayments/order_status_paid_createinvoice') == 1 ) {
                            $this->debugLog('Creating invoice');
                        
                            //need to convert from order into invoice
                            $invoice = $order->prepareInvoice();
                            $invoice->register()->capture();
                            $invoice->sendEmail();

                            Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                            
                            $invoice_message = Mage::helper('xibpayments')->__('Invoice #%s created', $invoice->getIncrementId());
                        } else {
                            $this->debugLog('Skip Creating invoice (setting in backend)');                        

                            $invoice_message = Mage::helper('xibpayments')->__('Invoice not created');
                        }
                    }

                    $this->debugLog('Status 200 received, saving order');

                    $comment = Mage::helper('xibpayments')->__('Xibpayments Customer Information:')
                        . "\n<br>Order Id: " .          $this->getCallbackData('ref')
                        . "\n<br>isTest: " .            $this->getCallbackData('is_test')
                        . "\n<br>Transaction Id: " .    $this->getCallbackData('transactionid')
                        . "\n<br>Status: " .            $this->getCallbackData('status')
                        . "\n<br>Transaction Cost: " .  $this->getCallbackData('transaction_cost')
                        . "\n<br>"
                        . "\n<br>Customer Firstname: " .$this->getCallbackData('customer_firstname')
                        . "\n<br>Customer Lastname: " . $this->getCallbackData('customer_lastname')
                        . "\n<br>Customer Address: " .  $this->getCallbackData('customer_address')
                        . "\n<br>Customer City: " .     $this->getCallbackData('customer_city')
                        . "\n<br>Customer Zipcode: " .  $this->getCallbackData('customer_zipcode')
                        . "\n<br>Customer Country: " .  $this->getCallbackData('customer_countrycode')
                        . "\n<br>Customer Phone: " .    $this->getCallbackData('customer_phonenumber')
                        . "\n<br>Customer Email: " .    $this->getCallbackData('customer_email')
                        . "\n<br>Customer Ip: " .       $this->getCallbackData('customer_ip_address');

                    $order->addStatusToHistory(
                        $order->getStatus(),
                        $comment,
                        false
                    )->save();


                    $order->setState(
                        Mage::getStoreConfig('payment/xibpayments/order_status_paid'), 
                        true,
                        $invoice_message,
                        true
                    );

                    $order->save();
                    $order->sendNewOrderEmail();                        
                    $this->debugLog('Finished saving order.');                                
                   
                } else {
                
                    // Status other than 200 received; this can happen multuiple times.
                    // Do not set the order to cancelled here, but wait for the customer
                    // to return to the 'cancel' URL

                    $this->debugLog('Status 300 callback received');                                

                    $comment = Mage::helper('xibpayments')->__('Xib|payments Error Received:')
                        . "\n<br>isTest: " .            $this->getCallbackData('is_test')
                        . "\n<br>Status: " .            $this->getCallbackData('status')
                        . "\n<br>Error: " .             $this->getCallbackData('errordescription') . '('.$this->getCallbackData('errorcode').')';

                    $order->addStatusToHistory(
                        $order->getStatus(),
                        $comment,
                        false
                    )->save();

                }                
            }
        }
    }

    public function isInitializeNeeded()
    {
        return true;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage::getSingleton('sales/order_config')->getStateDefaultStatus($state));
        $stateObject->setIsNotified(false);
    }
}
