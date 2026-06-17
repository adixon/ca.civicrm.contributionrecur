<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Contributionrecur_Form_ContributionRecurSettings extends CRM_Core_Form {
  function buildQuickForm() {

    $this->add(
      'checkbox', // field type
      'complete', // field name
      ts('Complete generated contributions')
    );
    $this->add(
      'checkbox', // field type
      'no_billing_acheft', // field name
      ts('Exclude the billing fields for the offline ACHEFT processor')
    );
    $this->add(
      'checkbox', // field type
      'no_billing_cc', // field name
      ts('Exclude the billing fields for the offline CC processor')
    );
    $this->add(
      'checkbox', // field type
      'edit_extra', // field name
      ts('Enable extra edit fields for recurring contributions')
    );
    $this->add(
      'checkbox', // field type
      'no_receipts', // field name
      ts('Prevent all receipts for recurring contributions')
    );
    $this->add(
      'checkbox', // field type
      'simplify_updaterecur', // field name
      ts('Only allow amount update for self-service updates of recurring schedules.')
    );
    $this->add(
      'checkbox', // field type
      'membership_alignment', // field name
      ts('Update recurring schedules when an associated membership expiry changes to maintain alignment of dates.')
    );
    $this->add(
      'checkbox', // field type
      'trigger_payment_on_billing_update', // field name
      ts('When billing information is updated for a grace or expired recurring membership, trigger a payment.')
    );

    // allow selection of activity type for implicit membership renewal
    $result = civicrm_api3('OptionValue', 'get', array('sequential' => 1, 'return' => "value,label", 'option_group_id' => 'activity_type', 'rowCount' => 100, 'component_id' => array('IS NULL' => '1'), 'is_active' => 1,));
    $activity_types = array('0' => '-- none --');
    foreach($result['values'] as $activity_type) {
      $activity_types[$activity_type['value']] = $activity_type['label'];
    }
    $this->add(
      'select', // field type
      'activity_type_id', // field name
      ts('Select an activity type for implicit membership renewals.'),
      $activity_types
    );

    $days = array('-1' => 'disabled');
    for ($i = 1; $i <= 28; $i++) {
      $days["$i"] = "$i";
    }
    $attr =  array('size' => 29,
         'style' => 'width:150px',
         'required' => FALSE);
    $day_select = $this->add(
      'select', // field type
      'days', // field name
      ts('Restrict allowable days of the month for recurring contributions (with one of my offline-processors only!).'),
      $days,
      FALSE,
      $attr
    );

    $day_select->setMultiple(TRUE);
    $day_select->setSize(29);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
    $result = CRM_Core_BAO_Setting::getItem('Recurring Contributions Extension', 'contributionrecur_settings');
    $defaults = (empty($result)) ? array('-1') : $result;
    $this->setDefaults($defaults);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();
    foreach(array('qfKey','_qf_default','_qf_ContributionRecurSettings_submit','entryURL') as $key) {
      if (isset($values[$key])) {
        unset($values[$key]);
      }
    }
    Civi::settings()->set('contributionrecur_settings',$values);
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
