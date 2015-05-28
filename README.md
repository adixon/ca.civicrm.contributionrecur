ca.civicrm.contribution_recur
=============================

Useful extensions of recurring contribution functionality in CiviCRM.

1. Reports

Backported and extended Jamie McClelland's Report in https://issues.civicrm.org/jira/browse/CRM-15453

2.  Auto-memberships
 
Memberships tied to recurring contributions have issues. Even non-recurring memberships are a bit unfriendly to manage. You would expect a contribution of type 'Membership Contribution' to auto-renew a membership, but it doesn't.

This extension provides a job that will identify contributions that should be associated with a membership but aren't, and try to apply them appropriately.

As an extra feature, you can configure the contribution type to switch type if an existing membership has already been paid up - allowing extra contributions to be deductible for example.

3. Restrict recurring options

You can force recurring options to be limited to specific day(s) of the month.
