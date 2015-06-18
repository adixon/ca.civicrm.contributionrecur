<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Contributionrecur_Form_ContributionRecurSettings extends CRM_Core_Form {
  function buildQuickForm() {
    $days = array('-1' => 'disabled');
    for ($i = 1; $i <= 28; $i++) {
      $days["$i"] = "$i";
    }
    $result = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
    $defaults = (empty($result)) ? array() : $result;
    $attr =  array('size' => 5,
         'style' => 'width:150px',
         'class' => 'advmultiselect',
         'required' => FALSE);
    $day_select = $this->add(
      'advmultiselect', // field type
      'days', // field name
      '<h3>'.ts('Restrict allowable days of the month for recurring contributions.').'</h3>'.ts('To disable this functionality and allow any day to be selected, choose the "disabled" option as the only Allowed day.'),
      $days,
      FALSE,
      $attr
    );
    
    $day_select->setLabel(array('Recurring days', 'Disallowed', 'Allowed'));
    $this->add(
      'checkbox', // field type
      'edit_extra', // field name
      ts('Enable extra edit fields for recurring contributions.')
    );
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
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
    civicrm_api3('Setting', 'create', array('domain_id' => 'current_domain', 'contributionrecur_settings' => $values));
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
