<?php

require_once 'contributionrecur.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function contributionrecur_civicrm_config(&$config) {
  _contributionrecur_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function contributionrecur_civicrm_xmlMenu(&$files) {
  _contributionrecur_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function contributionrecur_civicrm_install() {
  _contributionrecur_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function contributionrecur_civicrm_uninstall() {
  _contributionrecur_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function contributionrecur_civicrm_enable() {
  _contributionrecur_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function contributionrecur_civicrm_disable() {
  _contributionrecur_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function contributionrecur_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _contributionrecur_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function contributionrecur_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'ca.civicrm.contributionrecur',
    'name' => 'ContributionRecur',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'Recurring Offline Credit Card Contribution',
      'title' => 'Offline Credit Card',
      'description' => 'Offline credit card dummy payment processor.',
      'class_name' => 'Payment_RecurOffline',
      'billing_mode' => 'form',
      'user_name_label' => 'Account (ignored)',
      'password_label' => 'Password (ignored)',
      'url_site_default' => 'https://github.com/adixon/ca.civicrm.contributionrecur',
      'url_site_test_default' => 'https://github.com/adixon/ca.civicrm.contributionrecur',
      'is_recur' => 1,
      'payment_type' => 1,
    ),
  );
  $entities[] = array(
    'module' => 'ca.civicrm.contributionrecur',
    'name' => 'ContributionRecurACHEFT',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'Recurring Offline ACH/EFT Contribution',
      'title' => 'Offline ACH/EFT',
      'description' => 'Offline ACH/EFT dummy payment processor.',
      'class_name' => 'Payment_RecurOfflineACHEFT',
      'billing_mode' => 'form',
      'user_name_label' => 'Account (ignored)',
      'password_label' => 'Password (ignored)',
      'url_site_default' => 'https://github.com/adixon/ca.civicrm.contributionrecur',
      'url_site_test_default' => 'https://github.com/adixon/ca.civicrm.contributionrecur',
      'is_recur' => 1,
      'payment_type' => 2,
    ),
  );
  _contributionrecur_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function contributionrecur_civicrm_caseTypes(&$caseTypes) {
  _contributionrecur_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function contributionrecur_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _contributionrecur_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/*
 * Put my settings page into the navigation menu
 */
function contributionrecur_civicrm_navigationMenu(&$navMenu) {
  $item = array(
    'label' => 'Recurring Contributions Settings',
    'name' => 'Recurring Contributions Settings',
    'url' => 'civicrm/admin/contribute/recursettings',
    'permission' => 'access CiviContribute,administer CiviCRM',
    'operator'   => 'AND',
    'separator'  => NULL,
    'active'     => 1
  );
  // Check that our item doesn't already exist
  $menu_item_search = array('url' => $item['url']);
  $menu_items = array();
  CRM_Core_BAO_Navigation::retrieve($menu_item_search, $menu_items);
  if (empty($menu_items)) {
    $item['navID'] = 1 + CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_navigation");
    foreach ($navMenu as $key => $value) {
      if ('Administer' == $value['attributes']['name']) {
        $parent_key = $key;
        foreach($value['child'] as $child_key => $child_value) {
          if ('CiviContribute' == $child_value['attributes']['name']) {
            $item['parentID'] =  $child_key;
            $navMenu[$parent_key]['child'][$child_key]['child'][$item['navId']] = array(
              'attributes' => $item,
            );
            break;
          }
        }
      }
    }
  }
}

function _contributionrecur_civicrm_domain_info($key) {
  static $domain;
  if (empty($domain)) {
    $domain = civicrm_api('Domain', 'getsingle', array('version' => 3, 'current_domain' => TRUE));
  }
  switch($key) {
    case 'version':
      return explode('.',$domain['version']);
    default:
      if (!empty($domain[$key])) {
        return $domain[$key];
      }
      $config_backend = unserialize($domain['config_backend']);
      return $config_backend[$key];
  }
}

function _contributionrecur_civicrm_nscd_fid() {
  $version = _contributionrecur_civicrm_domain_info('version');
  return (($version[0] <= 4) && ($version[1] <= 3)) ? 'next_sched_contribution' : 'next_sched_contribution_date';
}

function contributionrecur_civicrm_varset($vars) {
  $version = CRM_Utils_System::version();
  if (version_compare($version, '4.5') < 0) { /// support 4.4!
    CRM_Core_Resources::singleton()->addSetting(array('contributionrecur' => $vars));
  }
  else {
    CRM_Core_Resources::singleton()->addVars('contributionrecur', $vars);
  }
}

/*
 * hook_civicrm_buildForm
 *
 * Do a Drupal 7 style thing so we can write smaller functions
 */
function contributionrecur_civicrm_buildForm($formName, &$form) {
  $fname = 'contributionrecur_'.$formName;
  if (function_exists($fname)) {
    $fname($form);
  }
  // else { echo $fname; die(); }
}

/*
 * hook_civicrm_pageRun
 *
 * Similar for pageRuns
 */
function contributionrecur_civicrm_pageRun(&$page) {
  $fname = 'contributionrecur_pageRun_'.$page->getVar('_name');
  if (function_exists($fname)) {
    $fname($page);
  }
  else { // echo $fname;
    // watchdog('civicustom','hook_civicrm_pageRun for page @name',array('@name' => $fname));
  }
}

/*
 * hook_civicrm_pre
 *
 * Intervene before recurring contribution records are created or edited, but only for my dummy processors.
 *
 * If the recurring days restriction settings are configured, then push the next scheduled contribution date forward to the first allowable one.
 * TODO: should there be cases where the next scheduled contribution is pulled forward? E.g. if it's still the next month and at least 15 days?
 */

function contributionrecur_civicrm_pre($op, $objectName, $objectId, &$params) {
  // since this function gets called a lot, quickly determine if I care about the record being created
  // watchdog('civicrm','hook_civicrm_pre for '.$objectName.' <pre>@params</pre>',array('@params' => print_r($params,TRUE)));
  switch($objectName) {
    case 'ContributionRecur':
      if (!empty($params['payment_processor_id'])) {
        $pp_id = $params['payment_processor_id'];
        $class_name = _contributionrecur_pp_info($pp_id,'class_name');
        // watchdog('civicrm','hook_civicrm_pre class name = <pre>'.print_r($class_name,TRUE).'</pre>');
        if ('Payment_RecurOffline' == substr($class_name,0,20)) {
          if ('create' == $op) {
            if (5 != $params['contribution_status_id'] && empty($params['next_sched_contribution_date'])) {
              $params['contribution_status_id'] = 5;
              // $params['trxn_id'] = NULL;
              $next = strtotime('+'.$params['frequency_interval'].' '.$params['frequency_unit']);
              $params['next_sched_contribution_date'] = date('YmdHis',$next);
            }
            if ('Payment_RecurOfflineACHEFT' == $class_name) {
              $params['payment_instrument_id'] = 5;
            }
          }
          if (!empty($params['next_sched_contribution_date'])) {
            $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
            $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
            if (0 < max($allow_days)) {
              $init_time = ('create' == $op) ? time() : strtotime($params['next_sched_contribution_date']);
              $from_time = _contributionrecur_next($init_time,$allow_days);
              $params['next_sched_contribution_date'] = date('YmdHis', $from_time);
            }
          }
        }
      }
      if (empty($params['installments'])) {
        $params['installments'] = '0';
      }
      break;
    case 'Contribution':
      if (!empty($params['contribution_recur_id'])) {
        $pp_id = _contributionrecur_payment_processor_id($params['contribution_recur_id']);
        if ($pp_id) {
          $class_name = _contributionrecur_pp_info($pp_id,'class_name');
          if ('create' == $op && 'Payment_RecurOffline' == substr($class_name,0,20)) {
            if ('Payment_RecurOfflineACHEFT' == $class_name) {
              $params['payment_instrument_id'] = 5;
            }
            $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
            $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
            if (0 < max($allow_days)) {
              $from_time = _contributionrecur_next(strtotime($params['receive_date']),$allow_days);
              $params['receive_date'] = date('Ymd', $from_time).'030000';
            }
          }
        }
      }
      break;
  }
}

/**
 * Implementation of hook_civicrm_validateForm().
 *
 * Prevent server validation of cc fields for my dummy cc processor
 *
 * @param $formName - the name of the form
 * @param $fields - Array of name value pairs for all 'POST'ed form values
 * @param $files - Array of file properties as sent by PHP POST protocol
 * @param $form - reference to the form object
 * @param $errors - Reference to the errors array.
 */
function contributionrecur_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if (isset($form->_paymentProcessor['class_name'])) {
    if ($form->_paymentProcessor['class_name'] == 'Payment_RecurOffline') {
      foreach(array('credit_card_number','cvv2') as $elementName) {
        if ($form->elementExists($elementName)){
          $element = $form->getElement($elementName);
          $form->removeElement($elementName, true);
          $form->addElement($element);
        }
      }
    }
    elseif ($form->_paymentProcessor['class_name'] == 'Payment_RecurOfflineACHEFT') {
      foreach(array('account_holder','bank_account_number','bank_identification_number','bank_name') as $elementName) {
        if ($form->elementExists($elementName)){
          $element = $form->getElement($elementName);
          $form->removeElement($elementName, true);
          $form->addElement($element);
        }
      }
    }
  }
}

/*
 * The contribution itself doesn't tell you which payment processor it came from
 * So we have to dig back via the contribution_recur_id that it is associated with.
 */
function _contributionrecur_payment_processor_id($contribution_recur_id) {
  $params = array(
    'version' => 3,
    'sequential' => 1,
    'id' => $contribution_recur_id,
    'return' => 'payment_processor_id'
  );
  $result = civicrm_api('ContributionRecur', 'getvalue', $params);
  if (empty($result)) {
    return FALSE;
    // TODO: log error
  }
  return $result;
}

/* 
 * See if I need to fix the payment instrument by looking for 
 * my offline recurring acheft processor
 * I'm assuming that other type 2 processors take care of themselves,
 * but you could remove class_name to fix them also
 */
function _contributionrecur_pp_info($payment_processor_id, $return, $class_name = NULL) {
  $params = array(
    'version' => 3,
    'sequential' => 1,
    'id' => $payment_processor_id,
    'return' => $return
  );
  if (!empty($class_name)) {
    $params['class_name'] = $class_name;
  }
  $result = civicrm_api('PaymentProcessor', 'getvalue', $params);
  if (empty($result)) {
    return FALSE;
    // TODO: log error
  }
  return $result;
}

/*
 * function _contributionrecur_next
 *
 * @param $from_time: a unix time stamp, the function returns values greater than this
 * @param $days: an array of allowable days of the month
 *
 * A utility function to calculate the next available allowable day, starting from $from_time.
 * Strategy: increment the from_time by one day until the day of the month matches one of my available days of the month.
 */
function _contributionrecur_next($from_time, $allow_mdays) {
  $dp = getdate($from_time);
  $i = 0;  // so I don't get into an infinite loop somehow
  while(($i++ < 60) && !in_array($dp['mday'],$allow_mdays)) {
    $from_time += (24 * 60 * 60);
    $dp = getdate($from_time);
  }
  return $from_time;
}

/*
 * hook_civicrm_buildForm for back-end contribution forms
 *
 * Allow editing of contribution amounts!
 */
function contributionrecur_CRM_Contribute_Form_Contribution(&$form) {
  // ignore this form unless I'm editing an contribution from my offline payment processor
  if (empty($form->_values['contribution_recur_id'])) {
    return;
  }
  $recur_id = $form->_values['contribution_recur_id'];
  $pp_id = _contributionrecur_payment_processor_id($recur_id);
  if ($pp_id) {
    $class_name = _contributionrecur_pp_info($pp_id,'class_name');
    if ('Payment_RecurOffline' == substr($class_name,0,20)) {
      $form->getElement('fee_amount')->unfreeze();
      $form->getElement('net_amount')->unfreeze();
    }
  }
}

/*
 * hook_civicrm_buildForm for public ("front-end") contribution forms
 *
 * Force recurring if it's an option on this form and configured in the settings
 * Add information about the next contribution if the allowed days are configured
 */
function contributionrecur_CRM_Contribute_Form_Contribution_Main(&$form) {
  // ignore this form if I have no payment processor or there's no recurring option
  if (empty($form->_paymentProcessors)) {
    return;
  }
  // if I'm using my dummy cc processor, modify the billing fields
  $class_name = $form->_paymentProcessor['class_name'];
  switch($class_name) {
    case 'Payment_RecurOffline': // cc offline
      $form->removeElement('credit_card_number',TRUE);
      // unset($form->_paymentFields['credit_card_number']);
      $form->addElement('text','credit_card_number',ts('Credit Card, last 4 digits'));
      $form->removeElement('cvv2',TRUE);
      unset($form->_paymentFields['cvv2']);
      break; 
  }

  if (empty($form->_elementIndex['is_recur'])) {
    return;
  }
  $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
  // if the site administrator has enabled forced recurring pages
  if (!empty($settings['force_recur'])) {
    // If a form enables recurring, and the force_recur setting is on, set recurring to the default and required
    $form->setDefaults(array('is_recur' => 1)); // make recurring contrib default to true
    $form->addRule('is_recur', ts('You can only use this form to make recurring contributions.'), 'required');
    contributionrecur_civicrm_varset(array('forceRecur' => '1'));
  }
  // if the site administrator has resticted the recurring days
  $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
  if (max($allow_days) > 0) {
    $next_time = _contributionrecur_next(strtotime('+1 day'),$allow_days);
    contributionrecur_civicrm_varset(array('nextDate' => date('Y-m-d', $next_time)));
  }
  if ((max($allow_days) > 0) || !empty($settings['force_recur'])) {
    CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/front.js');
  }
   
}

/* 
 * add some functionality to the update subscription form for recurring contributions 
 *
 * Todo: make the available new fields configurable
 */
function contributionrecur_CRM_Contribute_Form_UpdateSubscription(&$form) {
  // only do this if the user is allowed to edit contributions. A more stringent permission might be smart.
  if (!CRM_Core_Permission::check('edit contributions')) {
    return;
  }
  $settings = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
  // don't do this unless the site administrator has enabled it
  if (empty($settings['edit_extra'])) {
    return;
  }
  $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
  if (0 < max($allow_days)) {
    $userAlert = ts('Your next scheduled contribution date will automatically be updated to the next allowable day of the month: %1',array(1 => implode(',',$allow_days)));
    CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
  }
  $crid = CRM_Utils_Request::retrieve('crid', 'Integer', $form, FALSE);
  /* get the recurring contribution record and the contact record, or quit */
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  } 
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  try {
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $recur['contact_id']));
  } 
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  // turn off default notification checkbox, most will want to hide it as well.
  $defaults = array('is_notify' => 0);
  $edit_fields = array('contribution_status_id', 'next_sched_contribution_date','start_date');
  foreach($edit_fields as $fid) {
    $defaults[$fid] = $recur[$fid];
  }
  // print_r($recur); die();
  $form->addElement('static','contact',$contact['display_name']);
  // $form->addElement('static','contact',$contact['display_name']);
  $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
  $form->addElement('select', 'contribution_status_id', ts('Status'),$contributionStatus);
  $form->addDateTime('next_sched_contribution_date', ts('Next Scheduled Contribution'));
  $form->addDateTime('start_date', ts('Start Date'));
  $form->setDefaults($defaults);
  // now add some more fields for display only
  $pp_label = $form->_paymentProcessor['name']; // get my pp
  $form->addElement('static','payment_processor',$pp_label);
  $label = CRM_Contribute_Pseudoconstant::financialType($recur['financial_type_id']);
  $form->addElement('static','financial_type',$label);
  $labels = CRM_Contribute_Pseudoconstant::paymentInstrument();
  $label = $labels[$recur['payment_instrument_id']];
  $form->addElement('static','payment_instrument',$label);
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Contributionrecur/Subscription.tpl',
  ));
  CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/subscription.js');
}

/*
 *  Provide edit link for cancelled recurring contributions, allowing uncancel */
function contributionrecur_CRM_Contribute_Form_Search(&$form) {
  $version = CRM_Utils_System::version();
  if (version_compare($version, '4.5') < 0) { /// support 4.4!
    // a hackish way to inject these links into the form, they are displayed nicely using some javascript
    // js provided by contribextra extension!
  }
  else { // the new and better way as of 4.5
    CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/subscription_uncancel.js');
  }
}

/*
 * Display extra info on the recurring contribution view
 */
function contributionrecur_pageRun_CRM_Contribute_Page_ContributionRecur($page) {
  // get the recurring contribution record or quit 
  $crid = CRM_Utils_Request::retrieve('id', 'Integer', $page, FALSE);
  try {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', array('id' => $crid));
  } 
  catch (CiviCRM_API3_Exception $e) {
    return;
  }
  // show iats custom codes table data, if available
  $extra = _contributionrecur_get_iats_extra($recur);
  if (empty($extra)) {
    return;
  }
  $template = CRM_Core_Smarty::singleton();
  foreach($extra as $key => $value) {
    $template->assign($key, $value);
  }
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => 'CRM/Contributionrecur/ContributionRecur.tpl',
  ));
  CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/subscription_view.js');
}

/*
 * Add js to the summary page so it can be used on the financial/contribution tab */
function contributionrecur_pageRun_CRM_Contact_Page_View_Summary($page) {
  $contactId = CRM_Utils_Request::retrieve('cid', 'Positive');
  $recur_edit_url = CRM_Utils_System::url('civicrm/contribute/updaterecur','reset=1&action=update&context=contribution&cid='.$contactId.'&crid=');
  contributionrecur_civicrm_varset(array('recur_edit_url' => $recur_edit_url));
} 


function _contributionrecur_get_iats_extra($recur) {
  if (empty($recur['id']) && empty($recur['invoice_id'])) {
    return;
  }
  $extra = array();
  $params = array(1 => array('civicrm_iats_customer_codes', 'String'));
  $dao = CRM_Core_DAO::executeQuery("SHOW TABLES LIKE %1", $params);
  if (!empty($recur['id']) && $dao->fetch()) {
    $params = array(1 => array($recur['id'],'Integer'));
    $dao = CRM_Core_DAO::executeQuery("SELECT expiry FROM civicrm_iats_customer_codes WHERE recur_id = %1", $params);
    if ($dao->fetch()) {
      $expiry = str_split($dao->expiry,2);
      $extra['expiry'] = '20'.implode('-',$expiry);
    }
  }
  $params = array(1 => array('civicrm_iats_request_log', 'String'));
  $dao = CRM_Core_DAO::executeQuery("SHOW TABLES LIKE %1", $params);
  if (!empty($recur['invoice_id']) && $dao->fetch()) {
    $params = array(1 => array($recur['invoice_id'],'String'));
    $dao = CRM_Core_DAO::executeQuery("SELECT cc FROM civicrm_iats_request_log WHERE invoice_num = %1", $params);
    if ($dao->fetch()) {
      $extra['cc'] = $dao->cc;
    }
  }
  return $extra;
}

/**
 * For a given recurring contribution, find a reasonable candidate for a template, where possible
 */
function _contributionrecur_civicrm_getContributionTemplate($contribution) {
  // Get the first contribution in this series that matches the same total_amount, if present
  $template = array();
  $get = array('version'  => 3, 'contribution_recur_id' => $contribution['contribution_recur_id'], 'options'  => array('sort'  => ' id' , 'limit'  => 1));
  if (!empty($contribution['total_amount'])) {
    $get['total_amount'] = $contribution['total_amount'];
  }
  $result = civicrm_api('contribution', 'get', $get);
  if (!empty($result['values'])) {
    $contribution_ids = array_keys($result['values']);
    $template = $result['values'][$contribution_ids[0]];
    $template['line_items'] = array();
    $get = array('version'  => 3, 'entity_table' => 'civicrm_contribution', 'entity_id' => $contribution_ids[0]);
    $result = civicrm_api('LineItem', 'get', $get);
    if (!empty($result['values'])) {
      foreach($result['values'] as $initial_line_item) {
        $line_item = array();
        foreach(array('price_field_id','qty','line_total','unit_price','label','price_field_value_id','financial_type_id') as $key) {
          $line_item[$key] = $initial_line_item[$key];
        }
        $template['line_items'] = $line_item;
      }
    }
  }
  return $template;
}


