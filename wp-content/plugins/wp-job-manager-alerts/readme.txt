=== Job Alerts ===
Contributors: mikejolley
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.3.4
License: GNU General Public License v3.0

Allow users to subscribe to job alerts for their searches. Once registered, users can access a 'My Alerts' page which you can create with the shortcode [job_alerts].

Job alerts can be setup based on searches (by keyword, location keyword, category) which are delivered by email either daily, weekly or fortnightly.

= Documentation =

Usage instructions for this plugin can be found on the wiki: [https://github.com/mikejolley/WP-Job-Manager/wiki/Job-Alerts](https://github.com/mikejolley/WP-Job-Manager/wiki/Job-Alerts).

= Support Policy =

I will happily patch any confirmed bugs with this plugin, however, I will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please refer to the guide here: [http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Changelog ==

= 1.3.4 =
* "Alert me to jobs like this" link shown on single listings.
* Reset loop after emailing jobs.

= 1.3.3 =
* Fix region selection.

= 1.3.2 =
* Preserve spaces in search_location on add alert button.
* Switched page slug option to dropdown.

= 1.3.1 =
* Load translation files from the WP_LANG directory.
* Updated the updater class.

= 1.3.0 =
* Send alerts from noreply@yoursite.com
* Show nested categories on alerts form (please note, you will need to update your alert-form.php template if you've used an override.)

= 1.2.5 =
* Uninstaller.

= 1.2.4 =
* Don't enqueue chosen.

= 1.2.3 =
* Add a filter to the notifications query. job_manager_alerts_get_job_listings_args

= 1.2.2 =
* Use wp_schedule_event instead of wp_schedule_single_event.
* Correctly reschedule on edit.

= 1.2.1 =
* Fix wp_localize_script

= 1.2.0 =
* Ability to trigger an email alert from the job alerts shortcode.
* Added support for job regions plugin.

= 1.1.2 =
* Preserve spaces in search_keywords on add alert button

= 1.1.1 =
* Added POT file
* Fixed textdomain

= 1.1.0 =
* Added new updater - This requires a licence key which should be emailed to you after purchase. Past customers (via Gumroad) will also be emailed a key - if you don't recieve one, email me.

= 1.0.9 =
* Update textdomain

= 1.0.8 =
* Another fix to ensure the range filters are applied.

= 1.0.7 =
* Fix method_exists check

= 1.0.6 =
* Fix saving of taxonomies

= 1.0.5 =
* Missing localisation

= 1.0.4 =
* Fix issue when cats are disabled

= 1.0.3 =
* Fix found alerts check

= 1.0.2 =
* Added an option to disable alerts when no jobs are found.

= 1.0.1 =
* Only add alert link when page is set.

= 1.0.0 =
* First release.