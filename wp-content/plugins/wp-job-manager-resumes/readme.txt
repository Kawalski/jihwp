=== Resume Manager ===
Contributors: mikejolley
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.8.1
License: GNU General Public License v3.0

Manage canidate resumes from the WordPress admin panel, and allow candidates to post their resumes directly to your site.

= Documentation =

Usage instructions for this plugin can be found on the wiki: [https://github.com/mikejolley/WP-Job-Manager/wiki/Resume Manager](https://github.com/mikejolley/WP-Job-Manager/wiki/Resume Manager).

= Support Policy =

I will happily patch any confirmed bugs with this plugin, however, I will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please refer to the guide here: [http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Changelog ==

= 1.8.1 =
* Fix - Skill input.

= 1.8.0 =
* Added show_more and show_pagination arguments to the main shortcode.
* Added multi-select funtionality for categories for resume submission + resume filtering.
* Added filter for required/optional labels.
* Added ability for guests to submit resumes (but they cannot edit them!).
* Added tighter integration with the Job Applications plugin (so applications through resume manager can be saved in the database). Requires Applications 1.5.0.
* Added confirmation when removing education and experience.
* Fix - tinymce type checking.
* Tweak - Filter to disable chosen: job_manager_chosen_enabled (same as job manager core)
* Tweak - submit_resume_form_submit_button_text filter.
* Tweak - Pick up search_category from querystring to set default/selected category.
* Tweak - Added step input to submission form.

= 1.7.8 =
* the_candidate_video HTTPS fix.
* Add remove link to existing education/links.
* Improved uninstall script.

= 1.7.7 =
* Added dropdown to select resume submission page instead of slug option.
* Added 'add resume' link to candidate dashboard.

= 1.7.6 =
* Support skills for other field types.
* When creating a resume, copy candidate name to WP Profile (if not yet set).

= 1.7.5 =
* Fix access checks for guest posted resumes.
* Use ICL_LANGUAGE_CODE.

= 1.7.4 =
* Fix - Use triggerHandler() instead of trigger() in ajax-filters to prevent events bubbling up.
* Fix - Append current 'lang' to AJAX calls for WPML.
* Fix - When specifying categories on the jobs shortcode, don't clear those categories on reset.

= 1.7.3 =
* Fix resume file loop.

= 1.7.2 =
* Fix - Revised resume skills to work when slugs match. e.g. C++ C#, C
* resume_manager_user_can_download_resume_file filter

= 1.7.1 =
* Fix LinkedIn jquery.

= 1.7.0 =
* Mirroring WP Job Manager, added listing duration to resumes to allow them to expire/be relisted. Works in tandem with WC Paid Listings for charging submission and relisting.
* Added expirey field to backend.
* Improved post status display for resumes.
* Support html5 multiple files like WP Job Manager 1.14.
* Added video field for resumes.
* Added support for new field type in WP Job Manager 1.14.

= 1.6.4 =
* Fix category name display when using slugs.
* Fix text domains.

= 1.6.3 =
* Option to choose the role users get during registration.

= 1.6.2 =
* _candidate_title change to Professional to match frontend.
* Fix notice in update_resume_data.
* Fix resume_file notice.

= 1.6.1 =
* Fix updater.
* Job manager compat update.

= 1.6.0 =
* Confirm navigation when leaving the resume submission form.
* Added a new option to allow users to import their resume data from LinkedIn during submission.
* Added ability for users to make resumes hidden from their candidate dashboard (or publish them again).
* Added setting to automatically hide resumes after X days. Candidates can re-publish hidden resumes from their dashboard.
* Allow admin to 'feature' resumes, making them queryable and sticky.
* Fire updated_results hook after loading results.
* Fix submit_resume_form_fields_get_resume_data hook.

= 1.5.2 =
* Fix closing tag in view links.

= 1.5.1 =
* Show link to submit new resume to logged out users

= 1.5.0 =
* Additonal hooks in single template
* Extra args for submit_resume_form_save_resume_data
* Option to force users to apply via their online resume
* Built apply process into resume submission form

= 1.4.4 =
* Text domain fixes

= 1.4.3 =
* Added new updater - This requires a licence key which should be emailed to you after purchase. Past customers (via Gumroad) will also be emailed a key - if you don't recieve one, email me.

= 1.4.2 =
* Add posted by (author) setting in backend.
* Fix email URLs

= 1.4.1 =
* Jobify + WP SEO compatibility
* strtolower on capabilities

= 1.4.0 =
* Added the ability for logged in users to apply to a job with an on-file resume + include a custom message (requires Job Manager 1.9 and compatible template files)
* Added a way to have private share links for resumes (used in the apply feature). get_resume_share_link appends a key to the permalink and when present, any user can view the resume (even if standard permissions deny access).
* Drag drop sorting for education and experience fields on the resume submission form
* Template file for contact details.

= 1.3.0 =
* Improved search by including custom fields and comma separated keywords
* Get geolocation data for resumes
* Support for languages in the WP_LANG dir (subfolder wp-job-manager-resumes)

= 1.2.2 =
* Template files and functions for resume links

= 1.2.1 =
* New dir for resume files so protection does not affect old images

= 1.2.0 =
* Use GET vars to search resumes
* Added grunt
* Updated all text domains to wp-job-manager-resumes
* Fix wp-editor field
* Include POT file
* Added 'x' to remove education/exp/links
* Secure downloading of resumes and protected resumes directory with htaccess

= 1.1.2 =
* Fix remove link for uploaded files
* Fix path to fonts
* add education, experience, and links filters

= 1.1.1 =
* Fix class exists check for WP_Job_Manager_Writepanels

= 1.1.0 =
* Added resume file input. Enabled in settings. Requires Job Manager 1.7.3.
* Added download link for resume file to single resume page
* the_candidate_location_map_link filter

= 1.0.1 =
* PHP 5.2 compat

= 1.0.0 =
* First release.
