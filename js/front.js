/*
 * custom js for front end forms with recurring option 
 *   
 */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';
  var recurSettings = (typeof CRM.contributionrecur == 'undefined') ? CRM.vars.contributionrecur : CRM.contributionrecur;
  if (recurSettings.forceRecur == '1') {
    $('#is_recur').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
    });
  }
  if ("undefined" != typeof recurSettings.nextDate) {
    if (recurSettings.nextDate.length > 0) {
      $('.is_recur-section .content').append('<div class="description">'+ts('Your first contribution date will be %1.', {1:recurSettings.nextDate})+'</div>');
    }
  }
});

