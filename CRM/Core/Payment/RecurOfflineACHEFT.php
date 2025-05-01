<?php
/*
 * Placeholder clas for offline recurring payments
 */

class CRM_Core_Payment_RecurOfflineACHEFT extends CRM_Core_Payment {

  protected $_mode = NULL;
  protected $_settings = [];
  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_settings = Civi::settings()->get('contributionrecur_settings');
  }

  public function getBillingAddressFields($billingLocationID = NULL): array {
    return $this->_settings['no_billing_acheft'] ? [] : parent::getBillingAddressFields($billingLocationID);   
  }

  /**
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   */
  public function doDirectPayment(&$params) {
    if ($this->_mode == 'test') {
      $query             = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'test\\_%'";
      $p                 = array();
      $trxn_id           = strval(CRM_Core_Dao::singleValueQuery($query, $p));
      $trxn_id           = str_replace('test_', '', $trxn_id);
      $trxn_id           = intval($trxn_id) + 1;
      $params['trxn_id'] = sprintf('test_%08d', $trxn_id);
    }
    else {
      $query             = "SELECT MAX(trxn_id) FROM civicrm_contribution WHERE trxn_id LIKE 'live_%'";
      $p                 = array();
      $trxn_id           = strval(CRM_Core_Dao::singleValueQuery($query, $p));
      $trxn_id           = str_replace('live_', '', $trxn_id);
      $trxn_id           = intval($trxn_id) + 1;
      $params['trxn_id'] = sprintf('live_%08d', $trxn_id);
    }
    $params['gross_amount'] = $params['amount'];
    $params['payment_status_id'] = 1;
    return $params;
  }

  public function getPaymentFormFields() {
    return [];
  }
  
  /**
   * Are back office payments supported.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    return TRUE;
  }

  /**
   *
   */
  public function changeSubscriptionAmount(&$message = '', $params = array()) {
    $userAlert = ts('You have updated the amount of this recurring contribution.');
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    return TRUE;
  }

  /**
   *
   */
  public function cancelSubscription(&$message = '', $params = array()) {
    $userAlert = ts('You have cancelled this recurring contribution.');
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    return TRUE;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   */
  public function checkConfig() {
    return NULL;
  }

}

