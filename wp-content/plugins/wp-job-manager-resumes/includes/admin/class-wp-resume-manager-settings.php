<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Settings class.
 */
class WP_Resume_Manager_Settings extends WP_Job_Manager_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->settings_group = 'wp-job-manager-resumes';
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * init_settings function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function init_settings() {
		// Prepare roles option
		$roles         = get_editable_roles();
		$account_roles = array();

		foreach ( $roles as $key => $role ) {
			if ( $key == 'administrator' ) {
				continue;
			}
			$account_roles[ $key ] = $role['name'];
		}

		$this->settings = apply_filters( 'resume_manager_settings',
			array(
				'resume_listings' => array(
					__( 'Resume Listings', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'        => 'resume_manager_per_page',
							'std'         => '10',
							'placeholder' => '',
							'label'       => __( 'Resumes per page', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many resumes should be shown per page by default?', 'wp-job-manager-resumes' ),
							'attributes'  => array()
						),
						array(
							'name'       => 'resume_manager_enable_categories',
							'std'        => '0',
							'label'      => __( 'Resume categories', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume categories', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to enable resume categories. Categories must be setup by an admin for users to choose during job submission.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_enable_default_category_multiselect',
							'std'        => '0',
							'label'      => __( 'Multi-select Categories', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable category multiselect by default', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, the category select box will default to a multiselect on the [resumes] shortcode.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_category_filter_type',
							'std'        => 'any',
							'label'      => __( 'Category Filter Type', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, the category select box will default to a multiselect on the [resumes] shortcode.', 'wp-job-manager-resumes' ),
							'type'       => 'select',
							'options' => array(
								'any'  => __( 'Resumes will be shown if within ANY selected category', 'wp-job-manager-resumes' ),
								'all' => __( 'Resumes will be shown if within ALL selected categories', 'wp-job-manager-resumes' ),
							)
						),
						array(
							'name'       => 'resume_manager_enable_skills',
							'std'        => '0',
							'label'      => __( 'Resume skills', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume skills', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to enable the resume skills field. Skills work like tags and can be added by users during resume submission.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_enable_resume_upload',
							'std'        => '0',
							'label'      => __( 'Resume upload', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume upload', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to allow candidates to upload a resume file.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_enable_application',
							'std'        => '0',
							'label'      => __( 'Apply with resume', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow candidates to apply to jobs with resumes', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If a candidate applies with their online resume, the employer will be mailed their message and a private link to the resume. This works when the job application method is by email only (unless using the Job Applications addon as well).', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_force_application',
							'std'        => '0',
							'label'      => __( 'Force apply with resume', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Force candidates to create a resume and apply through resume manager', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Candidates without a resume on file will be taken through the resume submission process to apply for a job. Other details, such as the application email address, will be hidden. This works when the job application method is by email only (unless using the Job Applications addon as well).', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_autohide',
							'std'        => '',
							'label'      => __( 'Auto-hide resumes', 'wp-job-manager-resumes' ),
							'desc'       => __( 'How many <strong>days</strong> un-modified resumes should be published before being hidden. Can be left blank to never hide resumes automaticaly. Candidates can re-publish hidden resumes form their dashboard.', 'wp-job-manager-resumes' ),
							'attributes' => array()
						)
					),
				),
				'resume_submission' => array(
					__( 'Resume Submission', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'       => 'resume_manager_user_requires_account',
							'std'        => '1',
							'label'      => __( 'Account Required', 'wp-job-manager' ),
							'cb_label'   => __( 'Submitting listings requires an account', 'wp-job-manager' ),
							'desc'       => __( 'If disabled, non-logged in users will be able to submit listings without creating an account. Please note that this will prevent non-registered users from being able to edit their listings at a later date.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_enable_registration',
							'std'        => '1',
							'label'      => __( 'Account creation', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow account creation', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, non-logged in users will be able to create an account by entering their email address on the resume submission form.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'resume_manager_registration_role',
							'std'        => 'candidate',
							'label'      => __( 'Account Role', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If you enable registration on your submission form, choose a role for the new user.', 'wp-job-manager-resumes' ),
							'type'       => 'select',
							'options'    => $account_roles
						),
						array(
							'name'       => 'resume_manager_submission_requires_approval',
							'std'        => '1',
							'label'      => __( 'Approval Required', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'New submissions require admin approval', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, new submissions will be inactive, pending admin approval.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'        => 'resume_manager_submission_duration',
							'std'         => '',
							'label'       => __( 'Listing duration', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many <strong>days</strong> listings are live before expiring. Can be left blank to never expire. Expired listings must be relisted to become visible.', 'wp-job-manager-resumes' ),
							'attributes'  => array(),
							'placeholder' => __( 'Never expire', 'wp-job-manager-resumes' )
						),
						array(
							'name' 		=> 'resume_manager_submit_page_id',
							'std' 		=> '',
							'label' 	=> __( 'Submit Resume Page', 'wp-job-manager-resumes' ),
							'desc'		=> __( 'Select the page where you have placed the [submit_resume_form] shortcode. This lets the plugin know where the form is located.', 'wp-job-manager-resumes' ),
							'type'      => 'page'
						),
						array(
							'name' 		=> 'resume_manager_linkedin_import',
							'std'        => '0',
							'label'      => __( 'LinkedIn import', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow import of resume data from LinkedIn', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, users will be able to login to LinkedIn and have the resume submission form automatically populated.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						'api_key' => array(
							'name' 		=> 'job_manager_linkedin_api_key',
							'std' 		=> '',
							'label' 	=> __( 'LinkedIn API Key', 'wp-job-manager-resumes' ),
							'desc'		=> __( 'Get your API key by creating a new application on https://www.linkedin.com/secure/developer', 'wp-job-manager-resumes' ),
							'type'      => 'input'
						),
					)
				),
				'resume_visibility' => array(
					__( 'Resume Visibility', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'       => 'resume_manager_browse_resume_capability',
							'std'        => '',
							'label'      => __( 'Browse resume capability', 'wp-job-manager-resumes' ),
							'type'      => 'input',
							'desc'       => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to browse resumes.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' )
						),
						array(
							'name'       => 'resume_manager_view_resume_capability',
							'std'        => '',
							'label'      => __( 'View resume capability', 'wp-job-manager-resumes' ),
							'type'      => 'input',
							'desc'       => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to view a single resume.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' )
						),
						array(
							'name'       => 'resume_manager_contact_resume_capability',
							'std'        => '',
							'label'      => __( 'Contact details capability', 'wp-job-manager-resumes' ),
							'type'      => 'input',
							'desc'       => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to view contact details on a resume.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' )
						),
					),
				),
			)
		);

		if ( version_compare( JOB_MANAGER_VERSION, '1.7.3', '<' ) ) {
			unset( $this->settings['resume_listings'][1][3] );
		}
	}
}