=== Subscribe Google Groups ===
Contributors: ypcs
Donate link: https://seravo.com
Tags: google groups, email, subscribe, widget
Requires at least: 4.9
Tested up to: 4.9.5
Stable tag: trunk
Requires PHP: 5.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add widget(s) for subscribing to admin-specified Google Groups

== Description ==

This plugin adds new widget 'Subscribe Google Groups', which allows visitors to sign up to admin-specified Google Group.

After user as entered her e-mail address and submits the form, plugin (tries to) sends e-mail to GROUPNAME+subscribe@googlegroups.com. Afterwards, Google Groups sends confirmation link to submitter, and when user has clicked the link, she gets added to the group.

Note: If user domain has strict SPF/DKIM policies for e-mail handling, @googlegroups.com might reject their subscription request. In this case user needs to subscribe manually (by sending mail from her e-mail client or by visiting group page.)

Site can have 1-N widgets, each with their own configuration.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/subscribe-google-groups` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Appearance -> Widgets screen to add the widget to desired places in your layout
1. Configure widgets, ie. fill at least the name of your group, "slug"


== Frequently Asked Questions ==
None submitted yet.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* First release.

== Upgrade Notice ==

= 1.0 =
First release.
