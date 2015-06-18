{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{if $form.$elementName.label|strstr:"Array"}<h3>Allowable Days</h3><p>for recurring contributions</p>{else}{$form.$elementName.label}{/if}</pre></div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
