<?php
/**
 * Implementation of contributionrecur_membershipImplicit
 *
 * Process the potential implicit membership contributions in $contact
 * $contact is an array with keys:
 * membership_type_id, contact_id, contribution_ids
 * contributions is an array of contributions with keys contribution id and array values receive date and total_amount
 * 
 * This is an internal function and requires the calling function to do any sanity checks, etc.
 */
function contributionrecur_membershipImplicit($contact, $contributions, $membership_types, $financial_type_id_convert = NULL) {

  $return[] = $contributions;
  // watchdog('contributionrecur','running membership implicit function for '.$contact['contact_id'].', '.$op.', <pre>@params</pre>',array('@params' => print_r($params, TRUE)));
  $contact_id = $contact['contact_id'];
  // print_r($membership_types);
  // only proceed if this contact has exactly one membership of the right kind and status (grace or lapsed)
  $p = array('contact_id' => $contact_id, 'status_id' => array('IN' => array(1,3,4)), 'membership_type_id' => array('IN' => array_keys($membership_types)));
  try{
    $membership = civicrm_api3('Membership', 'getsingle', $p);
    $membership_type = $membership_types[$membership['membership_type_id']];
    $total_amount = floatval(0);
    $applied_contributions = array();
    $start_date = '';
    do {
      $contribution = array_shift($contributions);
      $total_amount += $contribution['total_amount'];
      $start_date = max($start_date,$contribution['receive_date']);
      $applied_contributions[] = $contribution;
    } while (
      (empty($financial_type_id_convert) || ($total_amount < floatval($membership_type['minimum_fee'])))
       && count($contributions)
    );
    // are contributions enough to renew the membership?
    if ($total_amount < $membership_type['minimum_fee']) {
      return array('Total amount < minimum fee');
    }
    // if the contributions exceed the minimum and we've got a conversion financial type id, we'll split it over to an extra contribution.
    if (($total_amount > $membership_type['minimum_fee']) && !empty($financial_type_id_convert)) {
      // TODO
    }
    // figure out start and end dates of the membership and update it
    // $start_date = date('Y-m-d'); 
    $updated_membership = array('contact_id' => $contact_id, 'id' => $membership['id']);
    $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership['id'],date('YmdHis',strtotime($start_date)),$membership['membership_type_id'],1);
    $updated_membership['start_date'] = CRM_Utils_Array::value('start_date', $dates);
    $updated_membership['end_date'] = CRM_Utils_Array::value('end_date', $dates);
    $updated_membership['source'] = ts('Auto-renewed membership from contribution of implicit membership type');
    $updated_membership['status_id'] = 2; // always set to current now
    civicrm_api3('Membership','create',$updated_membership);

    // now assign all the applied contributions to this membeship
    foreach($applied_contributions as $contribution) {
      civicrm_api3('MembershipPayment','create', array('contribution_id' => $contribution['id'], 'membership_id' => $membership['id']));
    }
    $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
    $activity_type_id = $settings['activity_type_id'];
    if ($activity_type_id > 0) {
      civicrm_api3('Activity', 'create', array(
        'version'       => 3,
        'activity_type_id'  => $activity_type_id,
        'source_contact_id'   => $contact_id,
        /* 'source_record_id' => $membership['id'], */
        'subject'       => "Applied unallocated contributions to membership using implicit membership from contributions rule.",
        'status_id'       => 2,
        'activity_date_time'  => date("YmdHis"),)
      );
    }
    $return[] = 'Updated membership '.$membership['id'];
  }
  catch (CiviCRM_API3_Exception $e) {
    $return[] = $e->getMessage();
    $return[] = $p;
  }
  // now see if we can change the financial type of any extra contributions.
  // this is a bad idea, they should get converted before they are completed, otherwise the bookkeeping is bad.
  if (!empty($financial_type_id_convert) && count($contributions)) {
    foreach ($contributions as $contribution) { 
      $p = array('id' => $contribution['id'], 'financial_type_id' => $financial_type_id_convert);
      try {
        civicrm_api3('Contribution', 'create', $p);
        $return[] = 'Converted contribution '. $contribution['id'] .' of contact id '. $contact_id .' to financial type id '. $financial_type_id_convert;
      }
      catch (CiviCRM_API3_Exception $e) {
        $return[] = $e->getMessage();
        $return[] = $p;
      }
    }
  }
  return $return;
}

