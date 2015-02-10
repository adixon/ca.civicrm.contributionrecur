<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contributionrecur_Form_Report_Recur extends CRM_Report_Form {

  protected $_customGroupExtends = array('Contact');

  static private $nscd_fid = '';
  static private $processors = array();
  static private $version = array();
  static private $financial_types = array();

  function __construct() {

    self::$nscd_fid = _contributionrecur_civicrm_nscd_fid();
    self::$version = _contributionrecur_civicrm_domain_info('version');
    self::$financial_types = (self::$version[0] <= 4 && self::$version[1] <= 2) ? array() : CRM_Contribute_PseudoConstant::financialType();
    $params = array('version' => 3, 'sequential' => 1, 'is_test' => 0, 'return.name' => 1);
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    foreach($result['values'] as $pp) {
      self::$processors[$pp['id']] = $pp['name'];
    }
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts("Last name, First name"),
          ),
        ),
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'order_bys' => array(
          'email' => array(
            'title' => ts('Email'),
          ),
        ),
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'total_amount' => array(
            'title' => ts('Amount Contributed to date'),
            'statistics' => array(
              'sum' => ts("Total Amount contributed")
            ),
          ),
        ),
        'filters' => array(
          'total_amount' => array(
            'title' => ts('Total Amount'),
            'operatorType' => CRM_Report_Form::OP_FLOAT,
            'type' => CRM_Utils_Type::T_FLOAT,
          ),
        ),
      ),
      'civicrm_contribution_recur' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'order_bys' => array(
          'id' => array(
            'title' => ts("Series ID"),
          ),
          'amount' => array(
            'title' => ts("Amount"),
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
          ),
          'modified_date' => array(
            'title' => ts('Modified Date'),
          ),
          self::$nscd_fid  => array(
            'title' => ts('Next Scheduled Contribution Date'),
          ),
          'cycle_day'  => array(
            'title' => ts('Cycle Day'),
          ),
          'payment_processor_id' => array(
            'title' => ts('Payment Processor'),
          ),
        ),
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'recur_id' => array(
            'name' => 'id',
            'title' => ts('Series ID'),
          ),
          'invoice_id' => array(
            'title' => ts('Invoice ID'),
            'default' => FALSE,
          ),
          'currency' => array(
            'title' => ts("Currency")
          ),
          'amount' => array(
            'title' => ts('Amount'),
            'default' => TRUE,
          ),
          'contribution_status_id' => array(
            'title' => ts('Donation Status'),
          ),
          'frequency_interval' => array(
            'title' => ts('Frequency interval'),
            'default' => TRUE,
          ),
          'frequency_unit' => array(
            'title' => ts('Frequency unit'),
            'default' => TRUE,
          ),
          'installments' => array(
            'title' => ts('Installments'),
            'default' => TRUE,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
          ),
          'create_date' => array(
            'title' => ts('Create Date'),
          ),
          'modified_date' => array(
            'title' => ts('Modified Date'),
          ),
          'cancel_date' => array(
            'title' => ts('Cancel Date'),
          ),
          self::$nscd_fid => array(
            'title' => ts('Next Scheduled Contribution Date'),
          ),
          'next_scheduled_day'  => array(
            'name' => self::$nscd_fid,
            'dbAlias' => 'DAYOFMONTH(contribution_recur_civireport.next_sched_contribution)',
            'title' => ts('Next Scheduled Day of the Month'),
          ),
          'cycle_day'  => array(
            'title' => ts('Cycle Day'),
          ),
          'failure_count' => array(
            'title' => ts('Failure Count'),
          ),
          'failure_retry_date' => array(
            'title' => ts('Failure Retry Date'),
          ),
          'payment_processor_id' => array(
            'title' => ts('Payment Processor'),
          ),
        ),
        'filters' => array(
          'contribution_status_id' => array(
            'title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(5),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'payment_processor_id' => array(
            'title' => ts('Payment Processor'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => self::$processors,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'currency' => array(
            'title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options'  => self::$financial_types,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'frequency_unit' => array(
            'title' => ts('Frequency Unit'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' =>  CRM_Core_OptionGroup::values('recur_frequency_units'),
          ),
          self::$nscd_fid  => array(
            'title' => ts('Next Scheduled Contribution Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'next_scheduled_day' => array(
            'title' => ts('Next Scheduled Day'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'cycle_day' => array(
            'title' => ts('Cycle Day'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'modified_date' => array(
            'title' => ts('Modified Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'cancel_date' => array(
            'title' => ts('Cancel Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      )
    );
    if (empty(self::$financial_types)) {
      unset($this->_columns['civicrm_contribution_recur']['filters']['financial_type_id']);
    }
    parent::__construct();
  }
  function getTemplateName() {
    return 'CRM/Report/Form.tpl' ;
  }

  function from() {
    $this->_from = "
      FROM civicrm_contact  {$this->_aliases['civicrm_contact']}
        INNER JOIN civicrm_contribution_recur   {$this->_aliases['civicrm_contribution_recur']}
          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution_recur']}.contact_id";
    $this->_from .= "
      LEFT JOIN civicrm_contribution  {$this->_aliases['civicrm_contribution']}
        ON ({$this->_aliases['civicrm_contribution_recur']}.id = {$this->_aliases['civicrm_contribution']}.contribution_recur_id AND 1 = {$this->_aliases['civicrm_contribution']}.contribution_status_id)";
    $this->_from .= "
      LEFT JOIN civicrm_email  {$this->_aliases['civicrm_email']}
        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id";
    $this->_from .= "
      LEFT  JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
        ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
       {$this->_aliases['civicrm_phone']}.is_primary = 1)";
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY " . $this->_aliases['civicrm_contribution_recur'] . ".id";
  }

  function alterDisplay(&$rows) {
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_sort_name', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
      }

      // handle contribution status id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_recur_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_recur_contribution_status_id'] = $contributionStatus[$value];
      }
      // handle processor id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_recur_payment_processor_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_recur_payment_processor_id'] = self::$processors[$value];
      }
    }
  }
}

