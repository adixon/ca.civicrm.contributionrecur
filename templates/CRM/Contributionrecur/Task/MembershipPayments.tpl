{* Confirmation of reversing membership contribution generation  *}
{* template to remove tags from contact  *}
<div class="crm-block crm-form-block crm-contact-task-form-block">
  <div class="messages status no-popup"><div class="icon inform-icon"></div>You have selected <strong>{$totalSelectedContacts}</strong> contact.</div>
  <div class="crm-section">
    <div class="label">{$form.donation_ft_id.label}</div>
    <div class="content">{$form.donation_ft_id.html}</div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.membership_ft_id.label}</div>
    <div class="content">{$form.membership_ft_id.html}</div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.membership_type_id.label}</div>
    <div class="content">{$form.membership_type_id.html}</div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.receive_date.label}</div>
    <div class="content">{include file="CRM/common/jcalendar.tpl" elementName=receive_date}</div>
  </div>
  <p>Clicking 'Generate Reversing Membership Contributions' will generate a pair of matching contributions for the date above and the types selected.</p>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
