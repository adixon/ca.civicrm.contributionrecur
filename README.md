ca.civicrm.contribution_recur
=============================

Useful extensions of recurring contribution functionality in CiviCRM.

 1. Reports

Backported and extended Jamie McClelland's Report in https://issues.civicrm.org/jira/browse/CRM-15453

 2.  Auto-memberships

Memberships tied to recurring contributions have issues. You would expect a contribution of type 'Membership Contribution' to auto-renew a membership, but it doesn't.

This extension provides a job that will identify recurring contributions that should be associated with a membership but aren't, and try to apply them appropriately.

As an extra feature, you can configure the contribution type to switch type if an existing membership has already been paid up - allowing extra contributions to be deductible for example.

The job has to be specially configured with at least two parameters:
mapping=financial_type_id:membership_type_id:overflow_financial_type_id
dateLimit=(something that strtotime can read)

The type_id's for the mapping can be multiple if you like.
You can also add these two paramenters
countLimit=(maximum number of contributions to process per job)
verbose=(if set, put a lot of debugging info into the job log)

You'll want to run this on a testing install and use the countLimit and verbose to take a look at what it's doing, before you set it up on a production install.

 3. Restrict recurring options

You can force recurring options to be limited to specific day(s) of the month. Configureable via a form accessible in the CiviContribute Admin menu.

4. Edit/View more fields for a recurring contribution series (also known as a "Subscription").

This is only wise for some payment processors (e.g. token based ones), so it's configurable and off by default. 
Extra editable fields include: Next scheduled contribution, and Status.
If you are using iATS as a payment processor, you can see the credit card expiry date, and last four digits of the card.
While we're in there, this also turns notification off by default.

