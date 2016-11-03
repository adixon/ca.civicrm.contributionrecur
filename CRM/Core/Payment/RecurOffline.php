<?php
/*
 * Placeholder clas for offline recurring payments
 */

class CRM_Core_Payment_RecurOffline extends CRM_Core_Payment {

  protected $_mode = NULL;

  protected $_params = array();

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

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
    $this->_processorName = ts('Recurring Offline Placeholder Processor');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $processorName = $paymentProcessor['name'];
    if (CRM_Utils_Array::value($processorName, self::$_singleton) === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_RecurOffline($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doDirectPayment(&$params) {

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
    return $params;
  }

  /** 
   * Are back office payments supported.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    return TRUE;
  }

  function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9001, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    return NULL;
  }
}

