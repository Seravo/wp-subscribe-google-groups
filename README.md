# # Wordpress Subscribe Google Groups -plugin

## UC Berkeley Modification

_Original README preserved below_

This widget is designed to allow a user to add themselves to a Google Group via the Wordpress site sidebar. In this adaptation of the original (produced by Seravo) the user may subscribe to a specific email hosted on `lists.berkeley.edu` rather than `googlegroups.com`. 

Otherwise, functionality is the same. A user provides the email where they wish to receive group messages. The plugin then sends an email to `GROUPNAME+subscribe@lists.berkeley.edu`, which should then return a confirmation email to the user allowing them to confirm the transaction. 

If WordPress detects an error while subscribing, the user gets an error message.


## Original

Adds new widget 'Subscribe Google Groups', that allows user to enter e-mail address she wants to get subscribed into admin-selected Google Group.

Behind the scenes plugin sends subscription e-mail to `GROUPNAME+subscribe@googlegroups.com`. If everything goes well, user gets notified for succesful operation. If WordPress detects error while subscribing, user gets error message.

## Known issues
If domain for the subscribing user has published strict SPF/DMARC policies, receiving domain might reject mail sent from invalid server (server hosting the WP site).

Also, see `grep -RE "(TODO|FIXME)" .`.
