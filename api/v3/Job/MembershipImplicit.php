<?php

/**
 * Job.MembershipImplicit API specification (optional)
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

}

/**
 * Job.MembershipImplicit API
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
  $dl = date('Y-m-d',strtotime($dateLimit));
  // throw new CRM_Core_Exception(ts('Date: '.$dl));
  $maps = explode(';',$maps);
  foreach($maps as $map) {
    list($ftype_ids,$mtype_ids) = explode(':',$map,2);
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
    $mtype_ids = implode(',',$clean);
    $sql = "SELECT c.id,c.contact_id,c.receive_date FROM civicrm_contribution c LEFT JOIN civicrm_membership_payment p ON c.id = p.contribution_id WHERE ISNULL(p.membership_id) AND (c.receive_date > '$dl') AND (c.financial_type_id in ($ftype_ids)) AND (c.contribution_status_id = 1) AND (c.contribution_recur_id > 0) ORDER BY contact_id, receive_date";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = array();
    while($dao->fetch()) {
      if (empty($contacts[$dao->contact_id])) {
        $contacts[$dao->contact_id] = array();
      } 
      $contacts[$dao->contact_id][] = array('id' => $dao->id, 'receive_date' => $dao->receive_date);
    }
    $update[$mtype_ids] = $contacts;
  } 
  if (count($update)) {
    include 'CRM/Contributionrecur/MembershipImplicit.php';
    $result = contributionrecur_membershipImplicit($update);
  }
    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
  if (!empty($result)) { 
    return civicrm_api3_create_success($result);
  } else {
    return civicrm_api3_create_success("Nothing to do!");
  }
}

