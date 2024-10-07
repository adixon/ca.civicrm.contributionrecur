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
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function contributionrecur_civicrm_install() {
  _contributionrecur_civix_civicrm_install();
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
}

/*
 * Put my settings page into the navigation menu
 */
function contributionrecur_civicrm_navigationMenu(&$navMenu) {
  $pages = array(
    'settings_page' => array(
      'label' => 'Recurring Contributions Settings',
      'name' => 'Recurring Contributions Settings',
      'url' => 'civicrm/admin/contribute/recursettings',
      'parent'    => array('Administer', 'CiviContribute'),
      'permission' => 'access CiviContribute,administer CiviCRM',
      'operator'   => 'AND',
      'separator'  => NULL,
      'active'     => 1
    ),
  );
  foreach ($pages as $item) {
    // Check that our item doesn't already exist.
    $menu_item_search = array('url' => $item['url']);
    $menu_items = array();
    CRM_Core_BAO_Navigation::retrieve($menu_item_search, $menu_items);
    if (empty($menu_items)) {
      $path = implode('/', $item['parent']);
      unset($item['parent']);
      _contributionrecur_civix_insert_navigation_menu($navMenu, $path, $item);
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
  $contributionrecur_settings = Civi::settings()->get('contributionrecur_settings');
  switch($objectName) {
    case 'ContributionRecur':
      if (!empty($params['payment_processor_id'])) {
        $pp_id = $params['payment_processor_id'];
        $class_name = _contributionrecur_pp_info($pp_id,'class_name');
        if ($class_name) {
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
              $allow_days = empty($contributionrecur_settings['days']) ? array('-1') : $contributionrecur_settings['days'];
              if (0 < max($allow_days)) {
                $init_time = ('create' == $op) ? time() : strtotime($params['next_sched_contribution_date']);
                $from_time = _contributionrecur_next($init_time,$allow_days);
                $params['next_sched_contribution_date'] = date('YmdHis', $from_time);
              }
            }
          }
        }
      }
      if (empty($params['installments'])) {
        $params['installments'] = '0';
      }
      if (!empty($contributionrecur_settings['no_receipts'])) {
        $params['is_email_receipt'] = 0;
      }
      break;
    case 'Contribution':
      if (!empty($params['contribution_recur_id'])) {
        $pp_id = _contributionrecur_payment_processor_id($params['contribution_recur_id']);
        if ($pp_id) {
          $class_name = _contributionrecur_pp_info($pp_id,'class_name');
          if ($class_name) {
            if ('create' == $op && 'Payment_RecurOffline' == substr($class_name,0,20)) {
              if ('Payment_RecurOfflineACHEFT' == $class_name) {
                $params['payment_instrument_id'] = 5;
              }
              $allow_days = empty($contributionrecur_settings['days']) ? array('-1') : $contributionrecur_settings['days'];
              if (0 < max($allow_days)) {
                $from_time = _contributionrecur_next(strtotime($params['receive_date']),$allow_days);
                $params['receive_date'] = date('Ymd', $from_time).'030000';
              }
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
  try {
    $result = civicrm_api3('ContributionRecur', 'getvalue', $params);
    // \Civi::log()->debug("_contributionrecur_payment_processor_id: contribution_recur_id: $contribution_recur_id, result: " . var_export($result, true));
    if (empty($result)) {
      \Civi::log()->error("_contributionrecur_payment_processor_id: contribution_recur_id: $contribution_recur_id, result is empty");
      $result = FALSE;
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    \Civi::log()->error("_contributionrecur_payment_processor_id: contribution_recur_id: $contribution_recur_id, Exception: $e");
    $result = FALSE;
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
  try {
    $result = civicrm_api('PaymentProcessor', 'getvalue', $params);
    // \Civi::log()->debug("_contributionrecur_pp_info: payment_processor_id: $payment_processor_id, result: " . var_export($result, true));
    if (empty($result)) {
      \Civi::log()->error("_contributionrecur_pp_info: payment_processor_id: $payment_processor_id, result is empty");
      $result = FALSE;
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    \Civi::log()->error("_contributionrecur_pp_info: payment_processor_id: $payment_processor_id, Exception: $e");
    $result = FALSE;
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
    if ($class_name) {
      if ('Payment_RecurOffline' == substr($class_name,0,20)) {
        foreach(array('fee_amount','net_amount') as $elementName) {
          if ($form->elementExists($elementName)){
            $form->getElement($elementName)->unfreeze();
          }
        }
      }
    }
  }
}

/*
 * hook_civicrm_buildForm for public ("front-end") contribution forms
 *
 * Force recurring if it's an option on this form and configured in the settings
 * Add information about the next contribution if the allowed days are configured
 * Do stuff for the nice js recurring switch if enabled
 */
function contributionrecur_CRM_Contribute_Form_Contribution_Main(&$form) {
  // ignore this form if I have no payment processor or there's no recurring option
  if (empty($form->_paymentProcessors)) {
    return;
  }
  // if I'm using my dummy cc processor, modify the billing fields
  switch(CRM_Utils_Array::value('class_name', $form->_paymentProcessor)) {
    case 'Payment_RecurOffline': // cc offline
      $form->removeElement('credit_card_number',TRUE);
      // unset($form->_paymentFields['credit_card_number']);
      $form->addElement('text','credit_card_number',ts('Credit Card, last 4 digits'));
      $form->removeElement('cvv2',TRUE);
      unset($form->_paymentFields['cvv2']);
      break;
  }

  if (empty($form->_elementIndex['is_recur']) && empty($form->_elementIndex['auto_renew'])) {
    return;
  }
  // get the default settings as well as the individual per-page settings
  $contributionrecur_settings = Civi::settings()->get('contributionrecur_settings');
  $page_id = $form->getVar('_id');
  $page_settings = Civi::settings()->get('contributionrecur_settings_'.$page_id);
  foreach(array('default_recur','force_recur','nice_recur','default_membership_auto_renew') as $setting) {
    if (!empty($page_settings[$setting])) {
      $contributionrecur_settings[$setting] = ($page_settings[$setting] > 0) ? 1 : 0;
    }
  }
  // if the site administrator has enabled forced recurring pages
  if (!empty($contributionrecur_settings['force_recur'])) {
    // If a form enables recurring, and the force_recur setting is on, set recurring to the default and required
    $form->setDefaults(array('is_recur' => 1)); // make recurring contrib default to true
    $form->addRule('is_recur', ts('You can only use this form to make recurring contributions.'), 'required');
    contributionrecur_civicrm_varset(array('forceRecur' => '1'));
  }
  elseif (!empty($contributionrecur_settings['nice_recur'])) {
    CRM_Core_Resources::singleton()->addStyleFile('ca.civicrm.contributionrecur', 'css/donation.css');
    CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/donation.js');
    // set the price field class names for use by the js, defaulting to the 'canonical' naming
    $nice_recur_names = ['monthly_gift','other_amount','one_time_gift','other_one_time_amount'];
    $nice_recur_settings = [];
    foreach($nice_recur_names as $machine_name) {
      $setting = 'name_'.$machine_name;
      $nice_recur_settings[$machine_name.'_section'] = '.' . (empty($page_settings[$setting]) ? $machine_name : $page_settings[$setting]) . '-section';
    }
    contributionrecur_civicrm_varset($nice_recur_settings);
  }
  if (!empty($contributionrecur_settings['default_membership_auto_renew'])) {
    // If the default_membership_auto_renew setting is on, alter the default value in the form
    $form->setDefaults(array('auto_renew' => 1)); // make recurring contrib default to true
    contributionrecur_civicrm_varset(array('defaultMembershipAutoRenew' => '1'));
    CRM_Core_Resources::singleton()->addScriptFile('ca.civicrm.contributionrecur', 'js/defaultMembershipAutoRenew.js');
  }
  if (!empty($contributionrecur_settings['default_recur'])) {
    $form->setDefaults(array('is_recur' => 1)); // make recurring contrib default to true
  }
  // if the site administrator has resticted the recurring days
  $allow_days = empty($contributionrecur_settings['days']) ? array('-1') : $contributionrecur_settings['days'];
  if (max($allow_days) > 0) {
    $next_time = _contributionrecur_next(strtotime('+1 day'),$allow_days);
    contributionrecur_civicrm_varset(array('nextDate' => date('Y-m-d', $next_time)));
  }
  if ((max($allow_days) > 0) || !empty($contributionrecur_settings['force_recur'])) {
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
  $contributionrecur_settings = Civi::settings()->get('contributionrecur_settings');
  // don't do this unless the site administrator has enabled it
  if (empty($contributionrecur_settings['edit_extra'])) {
    return;
  }
  $allow_days = empty($contributionrecur_settings['days']) ? array('-1') : $contributionrecur_settings['days'];
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
  $edit_fields = array(
    'contribution_status_id' => 'Status',
    'next_sched_contribution_date' => 'Next Scheduled Contribution',
    'start_date' => 'Start Date',
  );
  foreach(array_keys($edit_fields) as $fid) {
    if ($form->elementExists($fid)) {
      unset($edit_fields[$fid]);
    }
    else {
      $defaults[$fid] = $recur[$fid];
    }
  }
  if (0 == count($edit_fields)) { // assume everything is taken care of
    return;
  }
  $form->addElement('static','contact',$contact['display_name']);
  // $form->addElement('static','contact',$contact['display_name']);
  if ($edit_fields['contribution_status_id']) {
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $form->addElement('select', 'contribution_status_id', ts('Status'),$contributionStatus);
    unset($edit_fields['contribution_status_id']);
  }
  foreach($edit_fields as $fid => $label) {
    $form->addDateTime($fid,ts($label));
  }
  $form->setDefaults($defaults);
  // now add some more fields for display only
  $pp_label = $form->_paymentProcessor['name']; // get my pp
  $form->addElement('static','payment_processor',$pp_label);
  $label = CRM_Contribute_Pseudoconstant::financialType($recur['financial_type_id']);
  $form->addElement('static','financial_type',$label);
  $labels = CRM_Contribute_Pseudoconstant::paymentInstrument();
  $label = $labels[$recur['payment_instrument_id']];
  $form->addElement('static','payment_instrument',$label);
  $form->addElement('static','failure_count',$recur['failure_count']);
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
  // add the 'generate ad hoc contribution form' link
  $template = CRM_Core_Smarty::singleton();
  $adHocContributionLink = CRM_Utils_System::url('civicrm/contact/contributionrecur_adhoc', 'reset=1&cid='.$recur['contact_id'].'&paymentProcessorId='.$recur['payment_processor_id'].'&crid='.$crid.'&is_test='.$recur['is_test']);
  $template->assign('adHocContributionLink',
    '<a href="'.$adHocContributionLink.'">Generate</a>'
  );
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

/**
 * Implement hook_civicrm_searchTasks()
 *
 * Enable a simpler completion of pending contributions without sending emails, etc.
 */
function contributionrecur_civicrm_searchTasks($objectType, &$tasks ) {
  if ( $objectType == 'contribution' && CRM_Core_Permission::check('edit contributions')) {
    $tasks[] = array (
      'title' => ts('Convert Pending Offline Contributions to Completed', array('domain' => 'ca.civicrm.contributionrecur')),
      'class' => 'CRM_Contributionrecur_Task_CompletePending',
      'result' => TRUE);
  }
  elseif ( $objectType == 'contact' && CRM_Core_Permission::check('edit contributions')) {
    $tasks[] = array (
      'title' => ts('Generate Reversing Membership Payments', array('domain' => 'ca.civicrm.contributionrecur')),
      'class' => 'CRM_Contributionrecur_Task_MembershipPayments',
      'result' => TRUE);
  }
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
  // Get the most recent contribution in this series that matches the same total_amount, if present
  $template = array();
  $get = array('version'  => 3, 'contribution_recur_id' => $contribution['contribution_recur_id'], 'options'  => array('sort'  => ' id DESC' , 'limit'  => 1));
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

function contributionrecur_civicrm_tabset($tabsetName, &$tabs, $context) {
  //check if the tabset is Contribution Page
  if ($tabsetName == 'civicrm/admin/contribute') {
    if (!empty($context['contribution_page_id'])) {
      $contribID = $context['contribution_page_id'];
      $url = CRM_Utils_System::url( 'civicrm/admin/contribute/recur',
        "reset=1&snippet=5&force=1&id=$contribID&action=update&component=contribution" );
      //add a new Volunteer tab along with url
      $tab['recur'] = array(
        'title' => ts('Recurring'),
        'link' => $url,
        'valid' => 1,
        'active' => 1,
        'current' => false,
      );
    }
    if (!empty($context['urlString']) && !empty($context['urlParams'])) {
      $tab[] = array(
        'title' => ts('Recurring'),
        'name' => ts('Recurring'),
        'url' => $context['urlString'] . 'recur',
        'qs' => $context['urlParams'],
        'uniqueName' => 'recur',
      );
    }
    //Insert this tab into position 4
    $tabs = array_merge(
      array_slice($tabs, 0, 4),
      $tab,
      array_slice($tabs, 4)
    );
  }
}

// /**
//  * Implements hook_civicrm_postInstall().
//  *
//  * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
//  */
// function contributionrecur_civicrm_postInstall() {
//   _contributionrecur_civix_civicrm_postInstall();
// }

// /**
//  * Implements hook_civicrm_entityTypes().
//  *
//  * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
//  */
// function contributionrecur_civicrm_entityTypes(&$entityTypes) {
//   _contributionrecur_civix_civicrm_entityTypes($entityTypes);
// }
//

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add token services to the container.
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function contributionrecur_civicrm_container(ContainerBuilder $container) {
  $container->addResource(new FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    ['civi.token.list', 'contributionrecur_register_tokens']
  )->setPublic(TRUE);
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    ['civi.token.eval', 'contributionrecur_evaluate_tokens']
  )->setPublic(TRUE);
}

/*
 * contributionrecur_getFields()
 *
 * utility function to list the available fields in a contribution_recur record
 * keys is an array of which properties of the fields to get, e.g. 'name'
 * as the machine name and 'title' as the label.
 */
function contributionrecur_getFields($keys) {
  $fields = \Civi\Api4\ContributionRecur::getFields(FALSE);
  foreach($keys as $key) {
    $fields->addSelect($key);
  }
  return $fields->execute();
}

function contributionrecur_register_tokens(\Civi\Token\Event\TokenRegisterEvent $e) {
  $contribution_recur = $e->entity('contribution_recur');
  foreach (contributionrecur_getFields(['name','title']) as $field) {
    $contribution_recur->register($field['name'],$field['title']);
  }
}

/*
 * Provide values from the recurring contribution that is 'in progress' that is next scheduled to run
 */

function contributionrecur_evaluate_tokens(\Civi\Token\Event\TokenValueEvent $e) {
  foreach ($e->getRows() as $row) {
    $contactId = $row->context['contactId'];
    $contributionRecur = \Civi\Api4\ContributionRecur::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->addWhere('contribution_status_id:name', '=', 'In Progress')
      ->addWhere('next_sched_contribution_date', '>', 'NOW')
      ->addOrderBy('next_sched_contribution_date', 'ASC')
      ->setLimit(1)
      ->execute();
    /** @var TokenRow $row */
    $row->format('text/html');
    foreach (contributionrecur_getFields(['name','data_type']) as $field) {
      $field_name = $field['name'];
      if (!empty($contributionRecur[0][$field_name])) {
        switch($field['data_type']) {
          case 'Timestamp':
            $value = CRM_Utils_Date::formatDateOnlyLong($contributionRecur[0][$field_name]);
            break;
          default:
            $value = $contributionRecur[0][$field_name];
        }
        $row->tokens('contribution_recur', $field_name, $value);
      }
    }
  }
}
