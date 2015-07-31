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
      'edit_extra', // field name
      ts('Enable extra edit fields for recurring contributions.')
    );
    $this->add(
      'checkbox', // field type
      'force_recur', // field name
      ts('Force recurring-only option on pages that it is available.')
    );
    $days = array('-1' => 'disabled');
    for ($i = 1; $i <= 28; $i++) {
      $days["$i"] = "$i";
    }
    $result = civicrm_api3('Setting', 'getvalue', array('name' => 'contributionrecur_settings'));
    $defaults = (empty($result)) ? array('-1') : $result;
    $attr =  array('size' => 29,
         'style' => 'width:150px',
         'required' => FALSE);
    $day_select = $this->add(
      'select', // field type
      'days', // field name
      ts('Restrict allowable days of the month for recurring contributions.'),
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
