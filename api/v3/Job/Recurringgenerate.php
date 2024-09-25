<?php

/**
 * Job.Recurringgenerate API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_recurringgenerate_spec(&$spec) {
  $spec['payment_processor_id']['title'] = 'Payment Processor ID';
  $spec['payment_processor_id']['description'] = 'The Payment Processor ID';
  $spec['payment_processor_id']['type'] = CRM_Utils_Type::T_INT;
  $spec['financial_type_id'] = [
    'title' => 'Financial Type ID',
    'name' => 'financial_type_id',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'table' => 'civicrm_financial_type',
      'keyColumn' => 'id',
      'labelColumn' => 'name',
    ],
  ];
  $spec['id'] = array(
    'title' => 'Recurring payment id',
  );
  $spec['contact_id'] = array(
    'title' => 'Contact id',
  );
  $spec['catchup'] = array(
    'title' => 'Process as if in the past to catch up.',
    'api.required' => 0,
  );
  $spec['ignoremembership'] = array(
    'title' => 'Ignore memberships',
    'api.required' => 0,
  );
}

/**
 * Job.Recurringgenerate API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_recurringgenerate($params) {
  // TODO: what kind of extra security do we want or need here to prevent it from being triggered inappropriately? Or does it matter?
  if (empty($params['payment_processor_id']) 
      && empty($params['financial_type_id'])
      && empty($params['contact_id'])
      && empty($params['id'])
  ) return;
  // same these two extra params and remove them from the params array
  $catchup = !empty($params['catchup']);
  unset($params['catchup']);
  $domemberships = empty($params['ignoremembership']);
  unset($params['ignoremembership']);
  $contributionrecur_settings = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
  // new contributions are either complete or left pending, default pending
  $new_contribution_status_id = empty($contributionrecur_settings['complete']) ? 2 : 1;
  // running this job in parallell could generate bad duplicate contributions
  $lock = new CRM_Core_Lock('civimail.job.Recurringgenerate');
  $update = array();
  // $config = &CRM_Core_Config::singleton();
  // $debug  = false;
  // do my calculations based on yyyymmddhhmmss representation of the time
  // not sure about time-zone issues
  $dtCurrentDay    = date("Ymd", mktime(0, 0, 0, date("m") , date("d") , date("Y")));
  $dtCurrentDayStart = $dtCurrentDay."000000";
  $dtCurrentDayEnd   = $dtCurrentDay."235959";
  $expiry_limit = date('ym');
  // Before triggering payments, we need to do some housekeeping of the civicrm_contribution_recur records.
  // First update the end_date and then the complete/in-progress values.
  // We do this both to fix any failed settings previously, and also
  // to deal with the possibility that the settings for the number of payments (installments) for an existing record has changed.
  // First check for recur end date values on non-open-ended recurring contribution records that are either complete or in-progress
  $select = 'SELECT cr.*, count(c.id) AS installments_done, NOW() as test_now 
      FROM civicrm_contribution_recur cr 
      INNER JOIN civicrm_contribution c ON cr.id = c.contribution_recur_id 
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id 
      WHERE 
        (cr.installments > 0) 
        AND (c.total_amount > 0) 
        AND (cr.contribution_status_id IN (1,5)) 
  ';
  $spec = array();
  _civicrm_api3_job_recurringgenerate_spec($spec);
  $param_where = '';
  foreach($params as $key => $value) {
    if (isset($spec[$key])) {
      $f = explode(',',$value);
      $clean = array();
      foreach($f as $id) {
        if (!is_numeric($id) || empty($id)) {
          throw new CRM_Core_Exception(ts('Invalid syntax: '.$value));
        }
        else {
          $clean[] = (integer) $id;
        }
      }
      $ids = implode(',',$clean);
      $param_where .= ' AND (cr.'.$key.' IN ('.$ids.'))';
    }
  }
  $select .= $param_where .' GROUP BY c.contribution_recur_id';
  $dao = CRM_Core_DAO::executeQuery($select);
  while ($dao->fetch()) {
    // check for end dates that should be unset because I haven't finished
    if ($dao->installments_done < $dao->installments) { // at least one more installments
      if (($dao->end_date > 0) && ($dao->end_date <= $dao->test_now)) { // unset the end_date
        $update = 'UPDATE civicrm_contribution_recur SET end_date = NULL WHERE id = %1';
        CRM_Core_DAO::executeQuery($update,array(1 => array($dao->id,'Int')));
      }
    }
    // otherwise, check if my end date should be set to the past because I have finished
    elseif ($dao->installments_done >= $dao->installments) { // I'm done with installments
      if (empty($dao->end_date) || ($dao->end_date >= $dao->test_now)) { 
        // this interval complete, set the end_date to an hour ago
        $update = 'UPDATE civicrm_contribution_recur SET end_date = DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE id = %1';
        CRM_Core_DAO::executeQuery($update,array(1 => array($dao->id,'Int')));
      }
    }
  }
  // Second, make sure any open-ended recurring contributions have no end date set
  $update = 'UPDATE civicrm_contribution_recur cr 
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      SET
        cr.end_date = NULL 
      WHERE
        cr.contribution_status_id IN (1,5) 
        AND NOT(cr.installments > 0)
        AND NOT(ISNULL(cr.end_date))'.$param_where;
  CRM_Core_DAO::executeQuery($update);
  
  // Third, we update the status_id of the all in-progress or completed recurring contribution records
  // Unexpire uncompleted cycles
  $update = 'UPDATE civicrm_contribution_recur cr 
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      SET
        cr.contribution_status_id = 5 
      WHERE
        cr.contribution_status_id = 1 
        AND (cr.end_date IS NULL OR cr.end_date > NOW())'.$param_where;
  CRM_Core_DAO::executeQuery($update);
  // Expire badly-defined completed cycles
  $update = 'UPDATE civicrm_contribution_recur cr 
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      SET
        cr.contribution_status_id = 1 
      WHERE
        cr.contribution_status_id = 5 
        AND (
          (NOT(cr.end_date IS NULL) AND cr.end_date <= NOW())
          OR
          ISNULL(cr.frequency_unit)
          OR 
          (frequency_interval = 0) 
        )'.$param_where;
  CRM_Core_DAO::executeQuery($update);

  // Now we're ready to generate contribution records
  // Select the ongoing recurring payments where the next scheduled contribution date is before the end of of the current day
  $select = 'SELECT cr.*, pp.class_name, pp.is_test 
      FROM civicrm_contribution_recur cr 
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
      WHERE 
        cr.contribution_status_id = 5'.$param_where;
  //      AND pp.is_test = 0
  // process all recurring contributions due today or earlier
  $select .= ' AND cr.next_sched_contribution_date <= %1';
  $args[1] = array($dtCurrentDayEnd, 'String');
  $dao = CRM_Core_DAO::executeQuery($select,$args);
  $counter = 0;
  $output  = array();

  while ($dao->fetch()) {

    // Create all the contribution record with status = 2 (= pending), so that they must be completed manually. 
    // Try to get a contribution template for this contribution series - if none matches (e.g. if a donation amount has been changed), we'll just be naive about it.
    $contribution_template = _contributionrecur_civicrm_getContributionTemplate(array('contribution_recur_id' => $dao->id, 'total_amount' => $dao->amount));
    $contact_id = $dao->contact_id;
    $total_amount = $dao->amount;
    $hash = md5(uniqid(rand(), true));
    $contribution_recur_id    = $dao->id;
    $pp_type = $dao->class_name;
    $source = "Recurring Contribution (id=$contribution_recur_id, class=$pp_type)"; 
    $receive_date = $catchup ? strtotime($dao->next_sched_contribution_date) : time();
    // check if we already have an error
    $errors = array();
    $contribution = array(
      'version'        => 3,
      'contact_id'       => $contact_id,
      'receive_date'       => date('YmdHis',$receive_date),
      'total_amount'       => $total_amount,
      'payment_instrument_id'  => $dao->payment_instrument_id,
      'contribution_recur_id'  => $contribution_recur_id,
      'trxn_id'        => $hash, /* placeholder: just something unique that can also be seen as the same as invoice_id */
      'invoice_id'       => $hash,
      'source'         => $source,
      'contribution_status_id' => $new_contribution_status_id, 
      'currency'  => $dao->currency,
      'payment_processor'   => $dao->payment_processor_id,
      'is_test'        => $dao->is_test, /* propagate the is_test value from the parent contribution */
      'financial_type_id' => $dao->financial_type_id
    );
    // add any custom contribution values from the template
    foreach ($contribution_template as $field => $template_value) {
      if (substr($field, 0, 7) == 'custom_') {
        $contribution[$field] = is_array($template_value) ?  implode(', ',$template_value) : $template_value;
      }
    }
    // add some special values from the template
    $get_from_template = array('contribution_campaign_id','amount_level');
    foreach($get_from_template as $field) {
      if (isset($contribution_template[$field])) {
        $contribution[$field] = is_array($contribution_template[$field]) ?  implode(', ',$contribution_template[$field]) : $contribution_template[$field];
      }
    }
    if (!empty($contribution_template['line_items'])) {
      $contribution['skipLineItem'] = 1;
      $contribution[ 'api.line_item.create'] = $contribution_template['line_items'];
    }
    // create the pending contribution, and save its id
    $contributionResult = civicrm_api('contribution','create', $contribution);
    if (!empty($contributionResult['is_error'])) {
      civicrm_api3_create_error($contributionResult['error_message']);
      break;
    }
    $contribution_id = CRM_Utils_Array::value('id', $contributionResult);
    // if our template contribution has a membership payment, make this one also
    if ($domemberships && !empty($contribution_template['contribution_id'])) {
      try {
        $membership_payment = civicrm_api('MembershipPayment','getsingle', array('version' => 3, 'contribution_id' => $contribution_template['contribution_id']));
        if (!empty($membership_payment['membership_id'])) {
          civicrm_api('MembershipPayment','create', array('version' => 3, 'contribution_id' => $contribution_id, 'membership_id' => $membership_payment['membership_id']));
        }
      }
      catch (Exception $e) {
        // ignore, if will fail correctly if there is no membership payment
      }
    } 
    //$mem_end_date = $member_dao->end_date;
    // if our template contribution has a soft-credit, make this one also
    if (!empty($contribution_template['soft_credit'])) {
      foreach($contribution_template['soft_credit'] as $soft_credit) {
        $params = array(
          'sequential' => 1,
          'contribution_id' => $contribution_id,
          'contact_id' => $soft_credit['contact_id'],
          'amount' => $soft_credit['amount'],
          'currency' => $soft_credit['currency'],
          'soft_credit_type' => $soft_credit['soft_credit_type']
        );
        try {
          $result = civicrm_api3('ContributionSoft', 'create', $params);
        }
        catch (CiviCRM_API3_Exception $e) {
          CRM_Core_Error::debug_var('Unexpected Exception', $e);
          // just log the error and continue
        }
      }
    } 
    // $temp_date = strtotime($dao->next_sched_contribution);
    /* calculate the next collection date. You could use the previous line instead if you wanted to catch up with missing contributions instead of just moving forward from the present */
    $next_collectionDate = strtotime ("+$dao->frequency_interval $dao->frequency_unit", $receive_date);
    $next_collectionDate = date('YmdHis', $next_collectionDate);

    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_contribution_recur 
         SET next_sched_contribution_date = %1 
       WHERE id = %2
    ", array(
         1 => array($next_collectionDate, 'String'),
         2 => array($dao->id, 'Int')
       )
    );
    ++$counter;
  }

  // now update the end_dates and status for non-open-ended contribution series if they are complete (so that the recurring contribution status will show correctly)
  // This is a simplified version of what we did before the processing
  $select = 'SELECT cr.id, count(c.id) AS installments_done, cr.installments
      FROM civicrm_contribution_recur cr 
      INNER JOIN civicrm_contribution c ON cr.id = c.contribution_recur_id 
      INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id 
      WHERE 
        (cr.installments > 0) 
        AND (cr.contribution_status_id  = 5) '.$param_where.'
      GROUP BY c.contribution_recur_id';
  $dao = CRM_Core_DAO::executeQuery($select,$args);
  while ($dao->fetch()) {
    // check if my end date should be set to now because I have finished
    if ($dao->installments_done >= $dao->installments) { // I'm done with installments
      // set this series complete and the end_date to now
      $update = 'UPDATE civicrm_contribution_recur SET contribution_status_id = 1, end_date = NOW() WHERE id = %1';
      CRM_Core_DAO::executeQuery($update,array(1 => array($dao->id,'Int')));
    }
  }

  $lock->release();
  // If records were processed ..
  if ($counter) {
    return civicrm_api3_create_success(
      ts(
        '%1 contribution record(s) were processed.',
        array(
          1 => $counter
        )
      ) . "<br />" . implode("<br />", $output)
    );
  }
  // No records processed
  return civicrm_api3_create_success(ts('No contribution records were processed.'));
}
