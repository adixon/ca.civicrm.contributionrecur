/*
 * custom js for front end forms with recurring option 
 *   
 */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';
  var recurSettings = (typeof CRM.vars.contributionrecur != 'undefined') ? CRM.vars.contributionrecur : CRM.contributionrecur;
  if (recurSettings.forceRecur == '1') {
    $('#is_recur').prop('disabled',true);
  }
  if (recurSettings.nextDate.length > 0) {
    $('#recurHelp').append(ts('Your first contribution date will be %1.', {1:recurSettings.nextDate}));
  }
});

