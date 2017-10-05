# Wordpress Google Groups Subscribe

Adds new widget 'Google Groups Subscribe', that allows user to enter e-mail address she wants to get subscribed into admin-selected Google Group.

Behind the scenes plugin sends subscription e-mail to `GROUPNAME+subscribe@googlegroups.com`. If everything goes well, user gets notified for succesful operation. If WordPress detects error while subscribing, user gets error message.

## Known issues
If domain for the subscribing user has published strict SPF/DMARC policies, receiving domain might reject mail sent from invalid server (server hosting the WP site).
