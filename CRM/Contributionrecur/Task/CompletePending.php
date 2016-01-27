<?php
/**
 * This class provides the functionality to convert pending contributions to complete
 * without triggering emails, or worrying about related objects, etc.
 * Intended for simple recurring contributions generated as pending by this recurring contributions extension.
 */
class CRM_Contributionrecur_Task_CompletePending extends CRM_Contribute_Form_Task {

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
      $this->_contributionIds = array($id);
      $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContributions', 1);
    }
    else {
      parent::preProcess();
    }

    // check that all the contribution ids have pending status
    $query = "
SELECT count(*)
FROM   civicrm_contribution
WHERE  contribution_status_id != 2
AND    {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
    if ($count != 0) {
      CRM_Core_Error::statusBounce(ts('Please select only contributions with Pending status.'));
    }

    // we have all the contribution ids, so now we get the contact ids
    // parent::setContactIDs();
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

    $contribIDs = implode(',', $this->_contributionIds);
    $query = "
SELECT id,
       total_amount,
       financial_type_id,
       contact_id,
       trxn_id,
       invoice_id,
       receive_date,
       source
FROM   civicrm_contribution
WHERE  id IN ( $contribIDs )";
    $dao = CRM_Core_DAO::executeQuery($query,
      CRM_Core_DAO::$_nullArray
    );

    // build a row for each contribution id
    $this->_rows   = array();
    $amount = 0;
    while ($dao->fetch()) {
      $row['id'] = $dao->id;
      $amount += $row['total_amount'] = $dao->total_amount;
      $row['financial_type_id'] = $dao->financial_type_id;
      $row['contact_id'] = $dao->contact_id;
      $row['trxn_id'] = $dao->trxn_id;
      $row['invoice_id'] = $dao->invoice_id;
      $row['receive_date'] = $dao->receive_date;
      $row['source'] = $dao->source;
      $this->_rows[] = $row;
    }
    $this->assign('totalAmount', $amount);
    $this->assign('totalCount', count($this->_rows));
    $this->assign_by_ref('rows', $this->_rows);
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Complete All These Contributions'),
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
    foreach ($this->_rows as $row) {
      if (empty($row['trxn_id'])) {
        $row['trxn_id'] = $row['invoice_id'];
      }
      $row['contribution_status_id'] = 1;
      try {
        $contributionResult = civicrm_api3('contribution', 'create', $row);
      }
      catch (Exception $e) {
        throw new API_Exception('Failed to complete transaction: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
      }

    }
    CRM_Core_Session::setStatus(ts('Contribution status has been updated to complete for selected record(s).'), ts('Status Updated'), 'success');
  }
}

