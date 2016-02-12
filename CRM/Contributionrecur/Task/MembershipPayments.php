<?php
/**
 * This class provides the functionality to generate reversing membership contribution payments
 */
class CRM_Contributionrecur_Task_MembershipPayments extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */
  public $_single = FALSE;

  protected $_rows;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE
    );

    if ($id) {
      $this->_contactIds = array($id);
      $this->_componentClause = " contact_a.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContacts', 1);
    }
    else {
      parent::preProcess();
    }

    // check that all the contact ids have a membership of some status
    $query = "
SELECT count(*)
FROM   civicrm_contact contact_a LEFT JOIN civicrm_membership ON contact_a.id = civicrm_membership.contact_id
WHERE  ISNULL(civicrm_membership.id)
AND    {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
    if ($count != 0) {
      CRM_Core_Error::statusBounce(ts('Please select only contacts with a membership'));
    }

    $this->assign('single', $this->_single);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {

    $this->assign('totalCount', count($this->_contactIds));
    // and now add the configurable bits: financial type of member + reverse
    $params = array('sequential' => 1);
    $result = civicrm_api3('FinancialType', 'get', $params);
    $financial_types = array();
    foreach($result['values'] as $ft) {
      $financial_types[$ft['id']] = $ft['name'];
    }
    $params = array('sequential' => 1);
    $result = civicrm_api3('MembershipType', 'get', $params);
    $membership_types = array();
    foreach($result['values'] as $mt) {
      $membership_types[$mt['id']] = $mt['name'];
    }
    $this->addElement('select','membership_ft_id',ts('Membership Financial Type (to)'),$financial_types);
    $this->addElement('select','donation_ft_id',ts('Donation Financial Type (from)'),$financial_types);
    $this->addElement('select','membership_type_id',ts('Membership Type'),$membership_types);
    $this->addMoney(
      'amount', // field name
      'Amount', // field label
      TRUE, NULL, FALSE
    );
    $this->addDateTime('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Generate Reversing Membership Contributions'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'back',
          'name' => ts('Cancel'),
        ),
      )
    );

  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // for each contribution id, just update the contribution_status_id
    $results = array();
    $values = $this->exportValues();
    //print_r($values);
    //print_r($this->_rows);
    // print_r($membership_type); die();
    foreach ($this->_contactIds as $contact_id) {
      try {
        $membership = civicrm_api3('Membership', 'getsingle', array('sequential' => 1, 'contact_id' => $contact_id, 'membership_type_id' => $values['membership_type_id']));
      }
      catch (CiviCRM_API3_Exception $e) {
       // ignore
      }
      if (empty($membership['id'])) {
        $result = civicrm_api3('Membership', 'get', array('sequential' => 1, 'contact_id' => $contact_id, 'options' => array('limit' => 1, 'sort' => 'id DESC')));
        $membership = $result['values'][0];
      }
      try {
        // get details of last matching contribution
        $params = array('version' => 3, 'sequential' => 1, 'contact_id' => $contact_id, 'financial_type_id' => $values['donation_ft_id'], 'options' => array('limit' => 1, 'sort' => 'id DESC')); // , 'contribution_recur_id' => array('>','0'));
        $result = civicrm_api3('Contribution', 'get', $params);
        $contribution = $result['values'][0]; 
        $hash = md5(uniqid(rand(), true));
        $membership_contribution = array(
          'version'        => 3,
          'contact_id'       => $contact_id,
          'receive_date'       => $values['receive_date'],
          'total_amount'       => $values['amount'],
          'payment_instrument_id'  => $contribution['payment_instrument_id'],
          'contribution_recur_id'  => $contribution['contribution_recur_id'],
          'trxn_id'        => $hash, /* placeholder: just something unique that can also be seen as the same as invoice_id */
          'invoice_id'       => $hash,
          'source'         => 'Implicit membership account transfer',
          'contribution_status_id' => 1,
          'currency'  => $contribution['currency'],
          'payment_processor'   => $contribution['payment_processor'],
          'financial_type_id' => $values['membership_ft_id'],
        );
        $reversal_contribution = $membership_contribution;
        $reversal_contribution['total_amount'] = -$membership_contribution['total_amount'];
        $reversal_contribution['financial_type_id'] = $contribution['financial_type_id'];
        $reversal_contribution['trxn_id'] = $reversal_contribution['invoice_id'] = md5(uniqid(rand(), true));
        try {
          civicrm_api3('Contribution', 'create', $membership_contribution);
          civicrm_api3('Contribution', 'create', $reversal_contribution);
          civicrm_api3('MembershipPayment','create', array('contribution_id' => $membership_contribution['id'], 'membership_id' => $membership['id']));
          civicrm_api3('MembershipPayment','create', array('contribution_id' => $reversal_contribution['id'], 'membership_id' => $membership['id']));
          $return[] = 'Created reversal contributions for contact id '. $contact_id;
        }
        catch (CiviCRM_API3_Exception $e) {
          $return[] = $e->getMessage();
          $return[] = $p;
        }
      }
      catch (Exception $e) {
        throw new API_Exception('Error generating contributions: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
      }
    }
    CRM_Core_Session::setStatus(ts('Contributions have been generated for selected contact(s).'), ts('Contributions generated'), 'success');
  }
}

