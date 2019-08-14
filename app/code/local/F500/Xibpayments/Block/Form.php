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
    protected $_banks = array(
        '0031' => 'ABN Amro',
        '0081' => 'Fortis',
        '0091' => 'Friesland Bank',
        '0721' => 'ING',
        '0021' => 'Rabobank',
        '0751' => 'SNS Bank',
        '0511' => 'Triodos Bank',        
        '-'    => '',
        '0761' => 'ASN Bank',
        '0771' => 'SNS Regio Bank',
    );

    protected function _construct()
    {
        $this->setTemplate('xibpayments/form.phtml');
        parent::_construct();
    }
    
    public function getBanks() 
    {
        return $this->_banks;
    }
    
    public function getEnabledOptions()
    {
        $method = $this->getMethod();
        $options = explode(',',$method->getConfigdata('options'));
        return $options;    
    }
    
    public function getOptionTitle( $option )
    {
        $method = $this->getMethod();
        if ( $result = $method->getConfigData('title_' . $option) ) {
            return $result;
        }
        return '';
    }
}