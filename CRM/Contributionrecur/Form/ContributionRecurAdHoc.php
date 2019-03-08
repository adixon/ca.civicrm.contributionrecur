<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Contributionrecur_Form_ContributionRecurAdHoc extends CRM_Core_Form {

  protected function adHocContribution($values) {
    // generate another recurring contribution, matching our recurring template with submitted value
    $total_amount = $values['amount'];
    $contribution_template = _contributionrecur_civicrm_getContributionTemplate(array('contribution_recur_id' => $values['crid']));
    $contact_id = $values['cid'];
    $hash = md5(uniqid(rand(), true));
    $contribution_recur_id    = $values['crid'];
    $payment_processor_id = $values['paymentProcessorId'];
    $source = "Recurring Contribution (id=$contribution_recur_id)";
    $receive_date = date("YmdHis",strtotime($values['receive_date'])); 
    $contribution = array(
      'version'        => 3,
      'contact_id'       => $contact_id,
      'receive_date'       => $receive_date,
      'total_amount'       => $total_amount,
      'contribution_recur_id'  => $contribution_recur_id,
      'trxn_id' => $hash,
      'invoice_id'       => $hash,
      'source'         => $source,
      'contribution_status_id' => 2, /* initialize as pending, so we can run completetransaction after taking the money */
      'payment_processor'   => $payment_processor_id,
      'is_test'        => $values['is_test'], /* propagate the is_test value from the form */
    );
    foreach(array('payment_instrument_id','currency','financial_type_id') as $key) {
      $contribution[$key] = $contribution_template[$key];
    }
    // create the pending contribution, and save its id
    $contributionResult = civicrm_api('contribution','create', $contribution);
    $contribution_id = CRM_Utils_Array::value('id', $contributionResult);
    return 'Created new contribution id: '.$contribution_id;
  }


  function buildQuickForm() {

    $this->add('hidden','cid');
    $this->add('hidden','crid');
    $this->add('hidden','paymentProcessorId');
    $this->add('hidden','is_test');
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    $crid = CRM_Utils_Request::retrieve('crid', 'Integer');
    $paymentProcessorId = CRM_Utils_Request::retrieve('paymentProcessorId', 'Positive');
    $is_test = CRM_Utils_Request::retrieve('is_test', 'Integer');
    $defaults = array(
      'cid' => $cid,
      'crid' => $crid,
      'paymentProcessorId' => $paymentProcessorId,
      'is_test' => $is_test,
    );
    $this->setDefaults($defaults);
    /* show more details?  */
    /* $customer = $this->getCustomerCodeDetail($defaults);
    foreach($labels as $name => $label) {
      $iats_field = $iats_fields[$name];
      if (is_string($customer[$iats_field])) {
        $this->add('static', $name, $label, $customer[$iats_field]);
      }
    } */
    // todo: show past charges/dates ?

    // add form elements
    $this->addMoney(
      'amount', // field name
      'Amount', // field label
      TRUE, NULL, FALSE
    );
    $this->addDate(
      'receive_date', // field name
      ts('Received'), // field label
      FALSE
    );
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Generate Contribution'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Back')
      )
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();
    // print_r($values); die();
    // send charge request to iATS
    $result = $this->adHocContribution($values);
    $message = '<pre>'.print_r($result,TRUE).'</pre>';
    CRM_Core_Session::setStatus($message, 'Contribution generated'); // , $type, $options);
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
