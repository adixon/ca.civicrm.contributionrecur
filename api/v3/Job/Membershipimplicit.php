<?php
/**
 * This job finds all contributions of a specific type(s) that are not already linked to a membership
 * And applies it to the first membership of a specific type(s).
 *
 * Useful as a workaround for civicrm 4.4 and 4.5 issues relatd to recurring contributions and memberships
 * As well as for more complex business rules.
 */

/**
 * Job.Membershipimplicit API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_job_membershipimplicit_spec(&$spec) {
  // $spec['magicword']['api.required'] = 1;
  $spec['mapping'] = array(
    'title' => 'Map of financial type id(s) to membership type id(s).',
    'api.required' => 1,
  );
  $spec['dateLimit'] = array(
    'title' => 'Limit queries to this date',
    'api.required' => 1,
  );
  $spec['countLimit'] = array(
    'title' => 'Limit to this many contributions to process.',
    'api.required' => 0,
  );
  $spec['contribution_status'] = array(
    'title' => 'Process contributions of this status',
    'api.required' => 0,
  );
  $spec['verbose'] = array(
    'title' => 'Report verbosely.',
    'api.required' => 0,
  );
  $spec['create'] = array(
    'title' => 'Create new memberships of this type id, if none exist.',
    'api.required' => 0,
  );
  $spec['simulate'] = array(
    'title' => 'Simulate.',
    'api.required' => 0,
  );

}

/**
 * Job.Membershipimplicit API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_membershipimplicit($params = array()) {
  if (empty($params['mapping'])) return;
  if (empty($params['dateLimit'])) return;
  $update = array();
  $maps = $params['mapping'];
  $dateLimit = $params['dateLimit'];
  $countLimit = empty($params['countLimit']) ? '' : ' LIMIT '.((int)$params['countLimit']);
  $verbose = empty($params['verbose']) ? FALSE : TRUE;
  $create_new_membership_type_id = empty($params['create']) ? FALSE : ((int) $params['create']);
  $simulate = empty($params['simulate']) ? FALSE : TRUE;
  if (empty($params['contribution_status'])) {
    $contribution_status = 1;
  }
  else { // check that it's a comma separated list of integers
    $contribution_status_ids = explode(',',$params['contribution_status']);
    for ($i = 0; $i < count($contribution_status_ids); $i++) {
      $contribution_status_ids[$i] = (int) $contribution_status_ids[$i];
    }
    $contribution_status = implode(',',$contribution_status_ids);
  } 
  $dl = date('Y-m-d',strtotime($dateLimit));
  // throw new CRM_Core_Exception(ts('Date: '.$dl));
  $maps = explode(';',$maps);
  foreach($maps as $map) {
    list($ftype_ids,$mtype_ids,$membership_ftype_id) = explode(':',$map,3);
    $f = explode(',',$ftype_ids); 
    $clean = array();
    foreach($f as $id) {
      if (!is_numeric($id) || empty($id)) {
        throw new CRM_Core_Exception(ts('Invalid syntax: '.$ftype_ids));
      }
      else {
        $clean[] = (integer) $id;
      } 
    }
    $ftype_ids = implode(',',$clean);

    $m = explode(',',$mtype_ids); 
    $clean = array();
    foreach($m as $id) {
      if (!is_numeric($id) || empty($id)) {
        throw new CRM_Core_Exception(ts('Invalid syntax: '.$mtype_ids));
      }
      else {
        $clean[] = (integer) $id;
      } 
    }
    $mtype_ids = $clean;

    if (!empty($membership_ftype_id)) {
      $id = $membership_ftype_id;
      if (!is_numeric($id)) {
        throw new CRM_Core_Exception(ts('Invalid syntax: '.$membership_ftype_id));
      }
      else {
        $membership_ftype_id = (integer) $id;
      } 
    }      
    $sql = "SELECT c.id,c.contact_id,c.receive_date,c.total_amount,c.contribution_status_id FROM civicrm_contribution c INNER JOIN civicrm_line_item l ON c.id = l.contribution_id LEFT JOIN civicrm_membership_payment p ON c.id = p.contribution_id WHERE ISNULL(p.membership_id) AND (c.is_test = 0) AND (c.receive_date >= '$dl') AND (l.financial_type_id in ($ftype_ids)) AND (c.contribution_status_id IN ($contribution_status)) ORDER BY contact_id, receive_date".$countLimit;
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = array();
    while($dao->fetch()) {
      if (empty($contacts[$dao->contact_id])) {
        $contacts[$dao->contact_id] = array();
      } 
      // use the contribution id as a key to order them as input
      $contacts[$dao->contact_id][$dao->id] = array('id' => $dao->id, 'receive_date' => $dao->receive_date, 'total_amount' => $dao->total_amount, 'contribution_status_id' => $dao->contribution_status_id, 'applied' => 0);
    }
    // also deal with the possibility that the membership_payment records got created but no membership renewal happened
    /*
    $sql_m = "SELECT c.id,c.contact_id,c.receive_date,c.total_amount,c.contribution_status_id FROM civicrm_contribution c INNER JOIN civicrm_membership_payment p ON c.id = p.contribution_id INNER JOIN civicrm_membership m ON p.membership_id = m.id WHERE (m.status_id > 2) AND (c.receive_date >= '$dl') AND (c.financial_type_id in ($ftype_ids)) AND (c.contribution_status_id = 1) ORDER BY contact_id, receive_date".$countLimit;
    $dao = CRM_Core_DAO::executeQuery($sql_m);
    while($dao->fetch()) {
      if (empty($contacts[$dao->contact_id])) {
        $contacts[$dao->contact_id] = array();
      } 
      // use the contribution id as a key to order them as input
      $contacts[$dao->contact_id][$dao->id] = array('id' => $dao->id, 'receive_date' => $dao->receive_date, 'total_amount' => $dao->total_amount, 'contribution_status_id' => $dao->contribution_status_id, 'applied' => 1);
    }
    */
  } 
  $results = array();
  if (count($contacts)) {
    $result = civicrm_api3('MembershipType', 'get', array('sequential' => 1, 'id' => array('IN' => $mtype_ids)));
    if (!empty($result['values'])) {
      $membership_types = array();
      foreach($result['values'] as $membership_type) {
        $membership_types[$membership_type['id']] = $membership_type;
      }
      include 'CRM/Contributionrecur/MembershipImplicit.php';
      foreach($contacts as $contact_id => $contributions) {
        $results[] = $simulate ? $contact_id : contributionrecur_membershipImplicit(array('contact_id' => $contact_id), $contributions, array('membership_types' => $membership_types, 'membership_ftype_id' => $membership_ftype_id, 'create_new_membership_type_id' => $create_new_membership_type_id));
      }
    }
  }

  // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
  if (!empty($results)) { 
    $output = $verbose ? $sql.print_r($results, TRUE) : 'Processed '.count($results).' contacts'; 
    return civicrm_api3_create_success($output);
  } else {
    return civicrm_api3_create_success("Nothing to do! ".$sql);
  }
}

