/*
 * custom js for front end forms with default membership autorenew = on
 *   
 */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';
  var recurSettings = CRM.vars.contributionrecur;
  if (recurSettings.defaultMembershipAutoRenew == '1') {
    $('input[name="auto_renew"]').prop('checked',1);
    $("input[name^='price_']" ).change(function() {
      $('input[name="auto_renew"]').prop('checked',1);
    });
  }
});

