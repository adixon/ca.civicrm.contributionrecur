{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="form-item crm-block crm-form-block crm-contribution-form-block">
<div id="help">
    {ts}Use this form to update pending contributions to completed.{/ts}
</div>
<fieldset>
    <legend>{ts}Update Contribution Status to Completed{/ts}</legend>
<table>
<tr class="columnheader">
    <th class="right">{ts}Amount{/ts}&nbsp;&nbsp;</th>
    <th>{ts}Source{/ts}</th>
    <th>{ts}Receive Date{/ts}</th>
</tr>

{foreach from=$rows item=row}
<tr class="{cycle values="odd-row,even-row"}">
    <td class="right nowrap">{$row.total_amount|crmMoney}&nbsp;&nbsp;</td>
    <td>{$row.source}</td>
    <td>{$row.receive_date}</td>
</tr>
{/foreach}
</table>
  <div>{ts}Total Amount ={/ts} {$totalAmount|crmMoney}</div>
  <div>{ts}Number of Contributions ={/ts} {$totalCount}</div>
  <div class="crm-submit-buttons">{$form.buttons.html}</div>
</fieldset>
</div>
