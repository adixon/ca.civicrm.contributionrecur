ca.civicrm.contribution_recur
=============================

Useful extensions of recurring contribution functionality in CiviCRM. Particularly useful for larger installations, with some functions only available for some payment processors. You'll want to go the configuration screen in Admin -> CiviContribute -> Recurring Contribution Settings after enabling it.

## Reports

Backported and extended Jamie McClelland's Report in https://issues.civicrm.org/jira/browse/CRM-15453.

##  Auto-memberships

Memberships tied to recurring contributions have issues. You would expect a contribution of type 'Membership Contribution' to auto-renew a membership, but it doesn't necessarily.

This extension provides a job that will identify recurring contributions that should be associated with a membership but aren't, and try to apply them appropriately.

As an extra feature, you can configure it to generate matching/reversing contributions of a different type for the membership portion allowing extra contributions to be deductible for example.

The job has to be specially configured with at least two parameters:
<code><pre>
mapping=financial_type_id:membership_type_id:membership_financial_type_id
dateLimit=(something that strtotime can read)
</pre></code>

The type_id's for the mapping can be multiple if you like, separated by commas.

You can also add these two paramenters:
<code><pre>
countLimit=(maximum number of contributions to process per job)
verbose=(if set, put a lot of debugging info into the job log)
</pre></code>

You'll want to run this on a testing install and use the countLimit and verbose to take a look at what it's doing, before you set it up on a production install.

## Restrict recurring days option

You can force recurring options to be limited to specific day(s) of the month. Configureable via a form accessible in the CiviContribute Admin menu. Note that this functionality is primitive - it only works when the the schedule is managed by CiviCRM (e.g. with iATS Payments, or with the dummy offline processors provided by this extension), because all it does is push forward the next scheduled contribution to an allowed day when the schedule is first created. In the future, I hope that this feature can be implemented so that payment processors can override this behaviour appropriately.

## Force recurring option on pages where it is available ##

As an option, for (all) contribution pages that offer a recurring option - make it required and don't display it as an option. In other words, don't try to do recurring and non-recurring on the same contribution pages.

If you want to modify that behaviour per contribution page (e.g. for a specific contribution page enable that functionality or disable it from a default on), you can do that on the extension-provided "Recurring" tab per contribution page.

## Provide a js-based recurring/non-recurring switcher ##

A simple header block that enables/disables the recurring checkbox. Both a global configuration and a per-contribution-page override option.

## Edit/View more fields for a recurring contribution series (also known as a "Subscription").

This is only wise for some payment processors (e.g. token based ones), so it's configurable and off by default. 

Extra editable fields currently include: Next scheduled contribution, Start date, and Status.

Also display a few more of the fields for reference (contact name, financial type, payment processor).

When viewing a contribution, if you are using iATS as a payment processor, you can also see the credit card expiry date, and last four digits of the card.

While we're in there, this also turns notifications of changes off by default.

## Provide offline recurring dummy processors for both credit card and ACH/EFT ##

These integrate with the restrict recurring days options above, for example. 

## Offline recurring contribution job ##

You can use this with the dummy processors, or any other offline recurring contribution processors by configuring the job by processor id.

It has two additional useful parameters:
<code><pre>
catchup = boolean, set it to 1 to catchup if you forgot to or were unable to run the recurring contributions on time.
ignoremembership = boolean, set to 1 if you want to do your membership processing using the fancier job this extension provides.
</pre></code>

## Edit cancelled recurring schedules ##

Normally, once a schedule is cancelled, you can't uncancel it. In fact, the functionality for editing a cancelled schedule is still there, so this just gives you back the edit button which is now useful because you can edit the status. [functionality still in progress as of Oct 2015].

