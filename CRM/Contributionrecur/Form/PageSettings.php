<?php
/*
 */

/**
 * This class generates form components for Recurring Extra
 *
 */
class CRM_Contributionrecur_Form_PageSettings extends CRM_Contribute_Form_ContributionPage {

  public $_component = 'contribute';

  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = array();

    if (isset($this->_id)) {
      $page_id = $this->_id;
      $result = CRM_Core_BAO_Setting::getItem('Recurring Contributions Extension', 'contributionrecur_settings_'.$page_id);
      $defaults = (empty($result)) ? array() : $result;
      // $this->setDefaults($defaults);
      $this->assign('pageId', $page_id);
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->_last = TRUE;
    $options = array('0' => 'default', '1' => 'Yes', '-1' => 'No');
    $this->add(
      'select', // field type
      'force_recur', // field name
      ts('Force recurring-only on this page if available.'),
      $options
    );
    $this->add(
      'select', // field type
      'nice_recur', // field name
      ts('Add a nice js-based recurring/non-recurring switcher.'),
      $options
    );
    $this->add(
      'select', // field type
      'default_recur', // field name
      ts('Default the recurring checkbox to checked, but allow users to uncheck it.'),
      $options
    );
    $this->add(
      'text',
      'name_monthly_gift',
      ts('Machine name for monthly gift amount price field.'),
    );
    $this->add(
      'text',
      'name_other_amount',
      ts('Machine name for other monthly gift amount price field.'),
    );
    $this->add(
      'text',
      'name_one_time_gift',
      ts('Machine name for one-time gift price field.'),
    );
    $this->add(
      'text',
      'name_other_one_time_amount',
      ts('Machine name for other one-time gift amount price field.'),
    );
    /* $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    )); */
    $this->add(
      'select', // field type
      'default_membership_auto_renew', // field name
      ts('Modify default membership auto-renew to "on"'),
      $options
    );

    $this->assign('elementNames', $this->getRenderableElementNames());

    parent::buildQuickForm();
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    // get the submitted form values.
    $values = $this->controller->exportValues($this->_name);
    $contributionrecur_settings = array(
      'force_recur' => $values['force_recur'], 
      'nice_recur' => $values['nice_recur'], 
      'default_recur' => $values['default_recur'],
      'name_monthly_gift' => strtolower(CRM_Utils_String::munge($values['name_monthly_gift'], '_')),
      'name_other_amount' => strtolower(CRM_Utils_String::munge($values['name_other_amount'], '_')),
      'name_one_time_gift' => strtolower(CRM_Utils_String::munge($values['name_one_time_gift'], '_')),
      'name_other_one_time_amount' => strtolower(CRM_Utils_String::munge($values['name_other_one_time_amount'], '_')),
      'default_membership_auto_renew' => $values['default_membership_auto_renew'],
    );
    // Source
    $page_id = $this->_id;

    // CRM_Core_Error::debug_var('values', $values);
    CRM_Core_BAO_Setting::setItem($contributionrecur_settings, 'Recurring Contributions Extension', 'contributionrecur_settings_'.$page_id);
    // parent::endPostProcess();
    $this->controller->_destination = CRM_Utils_System::url('civicrm/admin/contribute/recur', 'reset=1&action=update&id='.$page_id);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Customize Recurring Behaviour');
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
