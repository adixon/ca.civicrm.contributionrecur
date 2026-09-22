<?php
/**
 *
 * @package CRM
 *
 */

class CRM_Contributionrecur_Form_Report_Recur extends CRM_Report_Form {

  protected $_customGroupExtends = ['Contact', 'Membership'];

  static private $processors = [];
  // static private $version = array();
  static private $financial_types = [];
  static private $prefixes = [];
  static private $contributionStatus = [];
  static private $membershipStatus = [];

  function __construct() {

    // self::$version = _contributionrecur_civicrm_domain_info('version');
    self::$financial_types = CRM_Contribute_PseudoConstant::financialType();
    self::$prefixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
    self::$contributionStatus = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    self::$membershipStatus = CRM_Member_PseudoConstant::membershipStatus();

    $params = ['version' => 3, 'sequential' => 1, 'is_test' => 0, 'return.name' => 1];
    $result = civicrm_api('PaymentProcessor', 'get', $params);
    foreach($result['values'] as $pp) {
      self::$processors[$pp['id']] = $pp['name'];
    }
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'order_bys' => [
          'sort_name' => [
            'title' => ts("Last name, First name"),
          ],
        ],
        'fields' => [
          'first_name' => [
            'title' => ts('First Name'),
          ],
          'last_name' => [
            'title' => ts('Last Name'),
          ],
          'prefix_id' => [
            'title' => ts('Prefix'),
          ],
          'external_identifier' => [
            'title' => ts('External Identifier'),
          ],
          'sort_name' => [
            'title' => ts('Contact Name'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'order_bys' => [
          'email' => [
            'title' => ts('Email'),
          ],
        ],
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'total_amount' => [
            'title' => ts('Amount Contributed to date'),
            'statistics' => [
              'sum' => ts("Total Amount contributed")
            ],
          ],
          'receive_date' => [
            'no_display' => TRUE,
          ],
        ],
        'filters' => [
          'total_amount' => [
            'title' => ts('Total Amount'),
            'operatorType' => CRM_Report_Form::OP_FLOAT,
            'type' => CRM_Utils_Type::T_FLOAT,
          ],
          'receive_date' => [
            'title' => ts('Receive Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'join_date' => [
            'title' => ts('Membership Join Date'),
          ],
          'start_date' => [
            'title' => ts('Membership Start Date'),
          ],
          'end_date' => [
            'title' => ts('Membership End Date'),
          ],
          'status_id' => [
            'title' => ts('Membership Status'),
          ],
        ],
        'filters' => [
          'end_date' => [
            'title' => ts('Membership End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
        'order_bys' => [
          'join_date' => [
            'title' => ts('Membership Join Date'),
          ],
          'start_date' => [
            'title' => ts('Membership Start Date'),
          ],
          'end_date' => [
            'title' => ts('Membership End Date'),
          ],
        ],
      ],
      'civicrm_contribution_recur' => [
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'order_bys' => [
          'id' => [
            'title' => ts("Series ID"),
          ],
          'amount' => [
            'title' => ts("Amount"),
          ],
          'start_date' => [
            'title' => ts('Start Date'),
          ],
          'modified_date' => [
            'title' => ts('Modified Date'),
          ],
          'next_sched_contribution_date'  => [
            'title' => ts('Next Scheduled Contribution Date'),
          ],
          'cycle_day'  => [
            'title' => ts('Cycle Day'),
          ],
          'failure_count'  => [
            'title' => ts('Failure Count'),
          ],
          'payment_processor_id' => [
            'title' => ts('Payment Processor'),
          ],
        ],
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'recur_id' => [
            'name' => 'id',
            'title' => ts('Series ID'),
          ],
          'invoice_id' => [
            'title' => ts('Invoice ID'),
            'default' => FALSE,
          ],
          'currency' => [
            'title' => ts("Currency")
          ],
          'amount' => [
            'title' => ts('Amount'),
            'default' => TRUE,
          ],
          'contribution_status_id' => [
            'title' => ts('Recurring Donation Status'),
          ],
          'frequency_interval' => [
            'title' => ts('Frequency interval'),
            'default' => TRUE,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency unit'),
            'default' => TRUE,
          ],
          'installments' => [
            'title' => ts('Installments'),
            'default' => TRUE,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
          ],
          'create_date' => [
            'title' => ts('Create Date'),
          ],
          'modified_date' => [
            'title' => ts('Modified Date'),
          ],
          'cancel_date' => [
            'title' => ts('Cancel Date'),
          ],
          'next_sched_contribution_date' => [
            'title' => ts('Next Scheduled Contribution Date'),
          ],
          'next_scheduled_day'  => [
            'name' => 'next_sched_contribution_date',
            'dbAlias' => 'DAYOFMONTH(contribution_recur_civireport.next_sched_contribution_date)',
            'title' => ts('Next Scheduled Day of the Month'),
          ],
          'cycle_day'  => [
            'title' => ts('Cycle Day'),
          ],
          'failure_count' => [
            'title' => ts('Failure Count'),
          ],
          'failure_retry_date' => [
            'title' => ts('Failure Retry Date'),
          ],
          'payment_processor_id' => [
            'title' => ts('Payment Processor'),
          ],
          'processor_id' => [
            'name' => 'processor_id',
            'title' => ts('Payment processor-specific client code'),
          ],
        ],
        'filters' => [
          'contribution_status_id' => [
            'title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => self::$contributionStatus,
            'default' => [5],
            'type' => CRM_Utils_Type::T_INT,
          ],
          'payment_processor_id' => [
            'title' => ts('Payment Processor'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => self::$processors,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'amount' => [
            'title' => ts('Recurring Amount'),
            'operatorType' => CRM_Report_Form::OP_FLOAT,
            'type' => CRM_Utils_Type::T_FLOAT,
          ],
          'currency' => [
            'title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options'  => self::$financial_types,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency Unit'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' =>  CRM_Core_OptionGroup::values('recur_frequency_units'),
          ],
          'next_sched_contribution_date'  => [
            'title' => ts('Next Scheduled Contribution Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'next_scheduled_day' => [
            'title' => ts('Next Scheduled Day'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'cycle_day' => [
            'title' => ts('Cycle Day'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'failure_count' => [
            'title' => ts('Failure Count'),
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'modified_date' => [
            'title' => ts('Modified Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'cancel_date' => [
            'title' => ts('Cancel Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
      ],
    ]  + $this->addAddressFields();
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
      LEFT JOIN civicrm_membership_payment 
        ON {$this->_aliases['civicrm_contribution']}.id = civicrm_membership_payment.contribution_id";
    $this->_from .= "
      LEFT JOIN civicrm_membership  {$this->_aliases['civicrm_membership']}
        ON civicrm_membership_payment.membership_id = {$this->_aliases['civicrm_membership']}.id";
    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY " . $this->_aliases['civicrm_contribution_recur'] . ".id";
  }

  function alterDisplay(&$rows) {
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
      if ($value = $row['civicrm_contribution_recur_contribution_status_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_contribution_recur_contribution_status_id'] = self::$contributionStatus[$value];
      }
      // handle membership status id
      if ($value = $row['civicrm_membership_status_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_membership_status_id'] = self::$membershipStatus[$value];
      }
      // handle processor id
      if ($value = $row['civicrm_contribution_recur_payment_processor_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_contribution_recur_payment_processor_id'] = self::$processors[$value];
      }
      // handle address country and province id => value conversion
      if ($value = $row['civicrm_address_country_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
      }
      if ($value = $row['civicrm_address_state_province_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
      }
      if ($value = $row['civicrm_contact_prefix_id'] ?? NULL) {
        $rows[$rowNum]['civicrm_contact_prefix_id'] = self::$prefixes[$value];
      }
    }
  }
}

