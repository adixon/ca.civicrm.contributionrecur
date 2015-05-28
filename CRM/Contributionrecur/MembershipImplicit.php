<?php
/**
 * Implementation of contributionrecur_membershipImplicit
 *
 * Process the potential implicit membership contributions in $updates
 * Updates is an array with keys of allowed membership ids, and an array of contact arrays, indexed by contact id, with values contribution id and receive date
 */
function contributionrecur_membershipImplicit($updates) {
  return 'test';
  // since this function gets called a lot, quickly determine if I care about the record being created
  if (('create' == $op) && ('Contribution' == $objectName)) {
    // watchdog('contributextra','hook_civicrm_pre for '.$objectName.', '.$op.', <pre>@params</pre>',array('@params' => print_r($params, TRUE)));
    $financial_type_id = $params['financial_type_id'];
    $contact_id = $params['contact_id'];
    // check for and set default
    $p = array('name' => 'membership_from_contribution_type_'.$financial_type_id);
    $result = civicrm_api3('Setting', 'getvalue', $p);
    if (!empty($result)) {
      $membership_type_id = $result;
      // get details, skip if that type no longer exists, for example
      $membership_type = civicrm_api3('membershipType','getsingle',array('membership_type_id' => $membership_type_id));
      if (empty($membership_type['id'])) {
        return;
      }
      /* get the possible alternative financial type */
      $p = array('name' => 'membership_from_contribution_type_'.$financial_type_id.'_financial_type_id');
      $result = civicrm_api3('Setting', 'getvalue', $p);
      $membership_financial_type_id = empty($result) ? '' : $result;
      /* 3 cases: extend an existing membership, change type, or create a new one */
      $existing_membership = array();
      $p = array('contact_id' => $contact_id,'membership_type_id' => $membership_type_id);
      $result = civicrm_api3('Membership', 'get', $p);
      if (!empty($result['values'])) { // found an existing one of the right type
        $existing_membership = reset($result['values']);
      }
      else { // try to find one of the wrong type (and change it)
        unset($p['membership_type_id']);
        $result = civicrm_api3('Membership', 'get', $p);
        if (!empty($result['values'])) { // found an existing one of the wrong type!
          $existing_membership = reset($result['values']);
        } 
      } 
      // figure out end date of membership
      // or even whether we can quit already
      $end_date = date('Y-m-d'); // default today for expired and new memberships
      if (!empty($existing_membership['end_date'])) {
        if (($existing_membership['end_date'] > $end_date) && $membership_financial_type_id) {
          // we don't need to use this contribution for membership after all!
          return;
        }
        if ($existing_membership['status_id'] <= 3) { // if current membership is 'active', use it's end date
          $end_date = $existing_membership['end_date'];
        }
      }
      $membership = array('contact_id' => $contact_id, 'membership_type_id' => $membership_type_id);
      if (!empty($existing_membership['id'])) {
        $membership['id'] = $existing_membership['id'];
        $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($existing_membership['id'],date('YmdHis',strtotime($end_date)),$membership_type_id,1);
        $membership['start_date'] = CRM_Utils_Array::value('start_date', $dates);
        $membership['end_date'] = CRM_Utils_Array::value('end_date', $dates);
        $membership['source'] = ts('Auto-renewed membership from contribution of implicit membership type');
      }
      else { // let civicrm calculate the end dates
        $membership['source'] = ts('Auto-created membership from contribution of implicit membership type');
      }     
      civicrm_api3('Membership','create',$membership);
      if ($membership_financial_type_id) {
        $params['financial_type_id'] = $membership_financial_type_id;
      }
    }
  }
  watchdog('contributextra','hook_civicrm_pre for '.$objectName.', '.$op.', <pre>@params</pre>',array('@params' => print_r($params)));
  // i only care about recurring contributions being created or edit, of the right financial type id
  if (('create' == $op || 'edit' == $op) && ('Contribution' == $objectName) && !empty($params['contribution_status_id']) && !empty($params['contribution_recur_id'])) {
    if ($params['contribution_status_id'] != 1) { // ignore non-completed contributions
      return;
    }
    $p = array('name' => 'membership_from_contribution_type_'.$params['financial_type_id']);
    $membership_implicit = civicrm_api3('Setting', 'getvalue', $p);
    if (empty($membership_implicit)) {
      return;
    }
    // ignore if this contribution is already attached to a membership
    $p = array('contribution_id' => $params['contribution_id']);
    $count = civicrm_api3('MembershipPayment', 'getcount', $p);
    if (!empty($count)) {
      return;
    }
    // check if the contact has exactly one membership of one of these kinds, and update their renewal, and add a correspondence in the membership_payment field
    $p = array('contact_id' => $params['contact_id'],'membership_type_id' => array('IN' => $membership_implicit));
    $membership = civicrm_api3('Membership', 'getsingle', $p);
    if (empty($membership['id'])) {
      return; 
    }
    $membership_type = civicrm_api3('MembershipType','getsingle', array('id' => $membership['membership_type_id']));
    if (empty($membership_type)) {
      return; // this is actually an unexpected error
    }
    $contribution_ids = array($params['contribution_id']);
    $total_amount = floatval($params['total_amount']);
    $minimum_fee = floatval($membership_type['minimum_fee']);
    if ($minimum_fee > $total_amount) {
      // this contribution isn't enough to pay for the membership on it's own, see if we can make use of past un-connected payments within the range of the membership type
      $since = strtotime('-'.$membership_type['duration_interval'].' '.$membership_type['duration_unit']);
      $since_date = date('Y-m-d',$since);
      $p = array('sequential' => 1, 'return' => 'id,total_amount', 'contact_id' => $params['contact_id'], 'financial_type_id' => $params['financial_type_id'], 'receive_date' => array('>=' => $since_date));
      $result = civicrm_api3('Contribution', 'get', $p);
      if (empty($result['count'])) {
        return;
      }
      foreach($result['values'] as $contribution) {
        // skip those payments that are already used for a membership
        $result = civicrm_api3('MembershipPayment','getcount',array('contribution_id' => $contribution['id']));
        if (empty($result['result'])) {
          $contribution_ids[] = $contribution['id'];
          $total_amount += $contribution['total_amount'];
          if ($total_amount >= $minimum_fee) {
            break;
          }
        }
      }
      if ($total_amount < $minimum_fee) { // still failed, quit
        return; 
      }
    }
    // otherwise, we're good to renew this membership and assign the contributions
    $start_date = date('Y-m-d');
    if ($membership['status_id'] <= 3) { // new current or grace, extend the date
      $start_date = $membership['end_date'];
    }
    $end_date = strtotime('+'.$membership_type['duration_interval'].' '.$membership_type['duration_unit'],$start_date);
    civicrm_api3('Membership','create', array('id' => $membership['id'],'end_date' => $end_date));
    foreach($contribution_ids as $contribution_id) {
      civicrm_api3('MembershipPayment','create', array('contribution_id' => $contribution_id, 'membership_id' => $membership['id']));
    }
    // now see if we need to change the financial type of this contribution
    $p = array('name' => 'membership_from_contribution_type_'.$params['financial_type_id'].'_financial_type_id');
    $membership_financial_type_id = civicrm_api3('Setting', 'getvalue', $p);
    if (!empty($membership_financial_type_id)) {
      $params['financial_type_id'] = $membership_financial_type_id;
    }
    array_shift($contribution_ids);
    foreach ($contribution_ids as $contribution_id) { // and the old ones as well ..
      $p = array('contribution_id' => $contribution_id, 'financial_type_id' => $membership_financial_type_id);
      civicrm_api3('Contribution','create');
    }
  }
}

