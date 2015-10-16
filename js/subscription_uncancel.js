/* custom js for the subscription form */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';
  var recurVars = ('contributionrecur' in CRM) ? CRM.contributionrecur : (('vars' in CRM) ? CRM.vars.contributionrecur : new Array());
  // console.log(recurVars.recur_edit_url);
  // var contactId = ('contributionrecur' in CRM) ? CRM.contributionrecur : (('vars' in CRM) ? CRM.vars.contributionrecur : new Array());
  // var unCancel = CRM.url('civicrm/contribute/updaterecur', {reset: 1, action: update, crid: , cid: , context: contribution});
  // $('.crm-recurcontrib-form-block table').append($('#contributionrecur-extra tr'));
  // $('.crm-recurcontrib-form-block table').prepend($('#contributionrecur-contact tr'));
});

