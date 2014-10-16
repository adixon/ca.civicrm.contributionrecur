<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Contributionrecur_Form_Report_Recur',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Recurring Contributions',
      'description' => 'Recurring Contributions Report',
      'class_name' => 'CRM_Contributionrecur_Form_Report_Recur',
      'report_url' => 'ca.civicrm.contributionrecur/recur',
      'component' => 'CiviContribute',
    ),
  ),
);
