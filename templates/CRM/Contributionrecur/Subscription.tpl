{*
 Extra fields for updating the subscription, buried in a fake table so I
 can move the rows where they belong
*}
<table id="contributionrecur-extra">
<tr><td class="label">{$form.contribution_status_id.label}</td>
        <td class="content">{$form.contribution_status_id.html}</td></tr>
<tr><td class="label">{$form.payment_processor_id.label}</td>
        <td class="content">{$form.payment_processor_id.html}</td></tr>
<tr><td class="label">{$form.next_sched_contribution_date.label}</td>
        <td class="content">{$form.next_sched_contribution_date.html}</td></tr>
</table>
<table id="contributionrecur-contact">
<tr><td class="label">Contact Name</td>
	<td class="content"><strong>{$form.contact.label}</strong></td></tr>
</table>
