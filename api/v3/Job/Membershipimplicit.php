<?php
/**
 * This job finds all recurring contributions of a specific type(s) that are not already linked to a membership
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
  $spec['verbose'] = array(
    'title' => 'Report verbosely.',
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
  $dl = date('Y-m-d',strtotime($dateLimit));
  // throw new CRM_Core_Exception(ts('Date: '.$dl));
  $maps = explode(';',$maps);
  foreach($maps as $map) {
    list($ftype_ids,$mtype_ids,$convert_ftype_id) = explode(':',$map,3);
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

    if (!empty($convert_ftype_id)) {
      $id = $convert_ftype_id;
      if (!is_numeric($id)) {
        throw new CRM_Core_Exception(ts('Invalid syntax: '.$convert_ftype_id));
      }
      else {
        $convert_ftype_id = (integer) $id;
      } 
    }      

    $sql = "SELECT c.id,c.contact_id,c.receive_date,c.total_amount FROM civicrm_contribution c LEFT JOIN civicrm_membership_payment p ON c.id = p.contribution_id WHERE ISNULL(p.membership_id) AND (c.receive_date > '$dl') AND (c.financial_type_id in ($ftype_ids)) AND (c.contribution_status_id = 1) AND (c.contribution_recur_id > 0) ORDER BY contact_id, receive_date".$countLimit;
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = array();
    while($dao->fetch()) {
      if (empty($contacts[$dao->contact_id])) {
        $contacts[$dao->contact_id] = array();
      } 
      // use the contribution id as a key to order them as input
      $contacts[$dao->contact_id][$dao->id] = array('id' => $dao->id, 'receive_date' => $dao->receive_date, 'total_amount' => $dao->total_amount);
    }
    // also deal with the possibility that the membership_payment records got created but no membership renewal happened
    $sql_m = "SELECT c.id,c.contact_id,c.receive_date,c.total_amount FROM civicrm_contribution c INNER JOIN civicrm_membership_payment p ON c.id = p.contribution_id INNER JOIN civicrm_membership m ON p.membership_id = m.id WHERE (m.status_id != 1) AND (c.receive_date > '$dl') AND (c.financial_type_id in ($ftype_ids)) AND (c.contribution_status_id = 1) AND (c.contribution_recur_id > 0) ORDER BY contact_id, receive_date".$countLimit;
    $dao = CRM_Core_DAO::executeQuery($sql_m);
    $contacts = array();
    while($dao->fetch()) {
      if (empty($contacts[$dao->contact_id])) {
        $contacts[$dao->contact_id] = array();
      } 
      // use the contribution id as a key to order them as input
      $contacts[$dao->contact_id][$dao->id] = array('id' => $dao->id, 'receive_date' => $dao->receive_date, 'total_amount' => $dao->total_amount);
    }
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
        $results[] = contributionrecur_membershipImplicit(array('contact_id' => $contact_id), $contributions, $membership_types, $convert_ftype_id);
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

