=== Job Applications ===
Contributors: mikejolley
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.6.0
License: GNU General Public License v3.0

Lets candidates submit applications to jobs which are stored on the employers jobs page, rather than simply emailed.

= Support Policy =

I will happily patch any confirmed bugs with this plugin, however, I will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please refer to the guide here: [http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Changelog ==

= 1.6.0 =
* Added [past_applications] shortcode.
* Strip invalid chars from file names.
* application_form_validate_fields hook.
* Only enable apply with X extensions for URL apply method if the application form is also enabled.
* Move handlers to wp_loaded hook.

= 1.5.2 =
* Allow job ID to be set when editing an application in the backend.
* create_job_application_notification_subject filter.

= 1.5.1 =
* Fix CSV download.

= 1.5.0 =
* Added tighter integration with Resume Manager (so applications through resume manager can be saved in the database). Requires Resume Manager 1.8.0.
* Integration with XING extension.
* Filter empty results from get_job_application_attachments.

= 1.4.7 =
* Removed "manage_applications" capabilitiy in favour of granular capabiltiies for all.

= 1.4.6 =
* Improved multiselect handling.

= 1.4.5 =
* Load translation files from the WP_LANG directory.
* Allow editing of before_message in sent email.
* Updated the updater class.

= 1.4.4 =
* Fixed textdomain.
* Uninstaller.
* Fixed resume IDs in select field.
* Fixed dashboard link.
* Only show resume link if the resume is published.

= 1.4.2 =
* Made 'online resume' field optional and provided a default 'N/A' value.

= 1.4.1 =
* Fixed multiple instances of uniqid() which made file upload urls differ.

= 1.4.0 =
* Don't send notification for linkedin applications.
* Removed integration with 'apply with ninja forms' due to API changes and lack of available hooks.
* Show online resume in the backend when set.

= 1.3.0 =
* HTML5 multiple file upload support.
* Multiple attachments enabled by default.
* Modifed attachment related functions.
* Requires Job Manager 1.14+
* Option to require registration to apply - application-form-login.php template controls the content.

= 1.2.0 =
* Refactor apply class to validate any custom defined fields.
* Filter send to address.
* Fix resume field validation.
* Export all custom fields in the CSV.

= 1.1.1 =
* Maintain field values on validation error.

= 1.1.0 =
* Fix issue with applications link when 'page' is greater than 1.
* Hide CV field error if field is unset.
* Attach user resume files to the notification email.
* Set Reply-To for notiifcation email.

= 1.0.3 =
* Fix updater conflict.
* Compatibility with Apply With Linkedin v2.0 (since the LinkedIn widget was deprecated)

= 1.0.2 =
* Only load JM classes when loaded.

= 1.0.1 =
* Added application count to job listing admin.

= 1.0.0 =
* First release.