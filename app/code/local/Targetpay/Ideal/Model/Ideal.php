<?php

/**

	iDEALplugins.nl
    TargetPay plugin v0.0.1 for Magento 1.4+

    (C) Copyright Yellow Melon 2013

 	@file 		iDEAL Model
	@author		Yellow Melon B.V. / www.idealplugins.nl

 */

require_once (BP . DS . 'app' . DS . 'code' . DS . 'local' . DS. "Targetpay" . DS . "targetpay.class.php");

class Targetpay_Ideal_Model_Ideal extends Mage_Payment_Model_Method_Abstract
	{

    protected $_code = 'ideal';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc 				= false;

    protected $_tp_method 				= "IDE";

    protected $_formBlockType = 'ideal/payment_form_ideal';

    /**
     * 	Prepare redirect that starts TargetPay payment
     */

	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('ideal/ideal/redirect', array('_secure' => true, 'bank_id' => $_POST["payment"]["bank_id"]));
		}

    /**
     * 	Start payment
     */

	public function setupPayment($bankId = false) {

    	$lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		$order = Mage::getModel('sales/order')->load($lastOrderId);

		if (!$order->getId()) {
			Mage::throwException('Cannot load order #' . $lastOrderId);
			}

		if($order->getGrandTotal() < 0.84) {
			Mage::throwException('The total amount should be at least 0.85');
			}

		$orderId = $order->getRealOrderId();
		$language = (Mage::app()->getLocale()->getLocaleCode() == 'nl_NL') ? "nl" : "en";
		$targetPay = new TargetPayCore ($this->_tp_method, Mage::getStoreConfig('payment/ideal/rtlo'), "f8ca4794a1792886bb88060ca0685c1e", $language, false);
		$targetPay->setAmount ( round($order->getGrandTotal() * 100));
		$targetPay->setDescription ( "Order #". $orderId );
		$targetPay->setBankId ( $bankId );
		$targetPay->setReturnUrl ( Mage::getUrl('ideal/ideal/return', array('_secure' => true, 'order_id' => $orderId) ));
		$targetPay->setReportUrl ( Mage::getUrl('ideal/ideal/report', array('_secure' => true, 'order_id' => $orderId) ));
		$bankUrl = $targetPay->startPayment();

		if (!$bankUrl) {
			Mage::throwException("TargetPay error: ". $targetPay->getErrorMessage() );
			}

		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$write->query("INSERT INTO `targetpay` SET `order_id`='".$write->quote($orderId)."', `method`='".$write->quote($this->_tp_method)."', `targetpay_txid`='".$write->quote($targetPay->getTransactionId())."'");

		return $bankUrl;
		}


    /**
     * 	Not implemented here
     */

	public function validatePayment($sOrderId) {
		}
	}

?>
