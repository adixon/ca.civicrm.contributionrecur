ca.civicrm.contribution_recur
=============================

Useful extensions of recurring contribution functionality in CiviCRM. Particularly useful for larger installations, with some functions only available for some payment processors.

## Reports

Backported and extended Jamie McClelland's Report in https://issues.civicrm.org/jira/browse/CRM-15453.

##  Auto-memberships

Memberships tied to recurring contributions have issues. You would expect a contribution of type 'Membership Contribution' to auto-renew a membership, but it doesn't necessarily.

This extension provides a job that will identify recurring contributions that should be associated with a membership but aren't, and try to apply them appropriately.

As an extra feature, you can configure the contribution type to switch type if an existing membership has already been paid up - allowing extra contributions to be deductible for example.

The job has to be specially configured with at least two parameters:
mapping=financial_type_id:membership_type_id:overflow_financial_type_id
dateLimit=(something that strtotime can read)

The type_id's for the mapping can be multiple if you like, separated by commas.

You can also add these two paramenters:
<code>countLimit=(maximum number of contributions to process per job)
<br />verbose=(if set, put a lot of debugging info into the job log)</code>

You'll want to run this on a testing install and use the countLimit and verbose to take a look at what it's doing, before you set it up on a production install.

## Restrict recurring days option

You can force recurring options to be limited to specific day(s) of the month. Configureable via a form accessible in the CiviContribute Admin menu. Note that this functionality is primitive - it only works when the the schedule is managed by CiviCRM (e.g. with iATS Payments), because all it does is push forward the next scheduled contribution to an allowed day when the schedule is first created. In the future, I hope that this feature can be implemented so that payment processors can override this behaviour appropriately.

## Edit/View more fields for a recurring contribution series (also known as a "Subscription").

This is only wise for some payment processors (e.g. token based ones), so it's configurable and off by default. 

Extra editable fields currently include: Next scheduled contribution, and Status.

When viewing a contribution, if you are using iATS as a payment processor, you can see the credit card expiry date, and last four digits of the card.

While we're in there, this also turns notifications of changes off by default.

