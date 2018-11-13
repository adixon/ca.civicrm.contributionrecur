/*
 * donation page extra js
 *   
 */

/*jslint indent: 2 */
/*global CRM, ts */

CRM.$(function ($) {
  'use strict';
  $('#priceset-div').before('<div class="gift-type-select"><div id="monthly-gift"><label>Monthly Gift</label></div><div id="one-time-gift"><label>One-time Gift</label></div></div>');
  if ($('#is_recur').prop('checked')) {
    setRecur();
    //console.log('checked');
  }
  else {
    setOneTime();
    //console.log('unchecked');
  }
  $('#one-time-gift').click(function() {
    $('#is_recur').prop('checked',false);
    setOneTime();
  });
  $('#monthly-gift').click(function() {
    $('#is_recur').prop('checked',true);
    setRecur();
  });
  $("#is_recur").change(function() {
    if (this.checked) {
      setRecur();
      // console.log('checked');
      // show only recur-relevant options
    }
    else {
      setOneTime();
      // console.log('unchecked');
    }
  });
  function setRecur() {
    $('#one-time-gift').removeClass('selected');
    $('#monthly-gift').addClass('selected');
    $('.one_time_gift-section').find('input').prop('checked',false);
    $('.one_time_gift-section').find('input').filter("[value='0']").trigger('click'); //.prop('checked',true);
    $('.one_time_gift-section').hide('slow');
    $('.other_one_time_amount-section').find('input').val('');
    $('.other_one_time_amount-section').hide('slow');
    $('.monthly_gift-section').find('input').prop('checked',false);
    $('.monthly_gift-section').show('slow');
    $('.other_amount-section').show('slow');
    // display(0);
  }
  function setOneTime() {
    $('#one-time-gift').addClass('selected');
    $('#monthly-gift').removeClass('selected');
    $('.monthly_gift-section').find('input').prop('checked',false);
    $('.monthly_gift-section').find('input').filter("[value='0']").trigger('click'); // .prop('checked',true);
    $('.monthly_gift-section').hide('slow');
    $('.other_amount-section').find('input').val('');
    $('.other_amount-section').hide('slow');
    $('.one_time_gift-section').find('input').prop('checked',false);
    $('.one_time_gift-section').show('slow');
    $('.other_one_time_amount-section').show('slow');
    // display(0);
  }
});


