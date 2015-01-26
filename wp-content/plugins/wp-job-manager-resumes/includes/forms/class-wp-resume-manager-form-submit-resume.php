<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Form_Submit_Resume class.
 */
class WP_Resume_Manager_Form_Submit_Resume extends WP_Job_Manager_Form {

	public    static $form_name = 'submit-resume';
	protected static $resume_id;
	protected static $job_id;
	protected static $preview_resume;
	protected static $steps;
	protected static $step = 0;

	/**
	 * Init form
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'process' ) );

		self::$steps  = (array) apply_filters( 'submit_resume_steps', array(
			'submit' => array(
				'name'     => __( 'Submit Details', 'wp-job-manager-resumes' ),
				'view'     => array( __CLASS__, 'submit' ),
				'handler'  => array( __CLASS__, 'submit_handler' ),
				'priority' => 10
				),
			'preview' => array(
				'name'     => __( 'Preview', 'wp-job-manager-resumes' ),
				'view'     => array( __CLASS__, 'preview' ),
				'handler'  => array( __CLASS__, 'preview_handler' ),
				'priority' => 20
			),
			'done' => array(
				'name'     => __( 'Done', 'wp-job-manager-resumes' ),
				'view'     => array( __CLASS__, 'done' ),
				'handler'  => array( __CLASS__, 'application_handler' ),
				'priority' => 30
			),
			'application_done' => array(
				'name'     => __( 'Application', 'wp-job-manager-resumes' ),
				'view'     => array( __CLASS__, 'application_done' ),
				'priority' => 40
			)
		) );

		uasort( self::$steps, array( __CLASS__, 'sort_by_priority' ) );

		// Get step/resume
		if ( ! empty( $_REQUEST['step'] ) ) {
			self::$step = is_numeric( $_REQUEST['step'] ) ? max( absint( $_REQUEST['step'] ), 0 ) : array_search( $_REQUEST['step'], array_keys( self::$steps ) );
		}
		self::$resume_id = ! empty( $_REQUEST['resume_id'] ) ? absint( $_REQUEST[ 'resume_id' ] ) : 0;
		self::$job_id    = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST[ 'job_id' ] ) : 0;

		if ( self::$resume_id ) {
			$resume_status = get_post_status( self::$resume_id );
			if ( 'expired' === $resume_status ) {
				if ( ! resume_manager_user_can_edit_resume( self::$resume_id ) ) {
					self::$resume_id = 0;
					self::$job_id    = 0;
					self::$step      = 0;
				}
			} elseif ( ! in_array( $resume_status, apply_filters( 'resume_manager_valid_submit_resume_statuses', array( 'preview' ) ) ) ) {
				self::$resume_id = 0;
				self::$job_id    = 0;
				self::$step      = 0;
			}
		}
	}

	/**
	 * Get step from outside of the class
	 */
	public static function get_step() {
		return self::$step;
	}

	/**
	 * Increase step from outside of the class
	 */
	public static function next_step() {
		self::$step ++;
	}

	/**
	 * Decrease step from outside of the class
	 */
	public static function previous_step() {
		self::$step --;
	}

	/**
	 * Sort array by priority value
	 */
	protected static function sort_by_priority( $a, $b ) {
		return $a['priority'] - $b['priority'];
	}

	/**
	 * Get the submitted resume ID
	 * @return int
	 */
	public static function get_resume_id() {
		return absint( self::$resume_id );
	}

	/**
	 * Get the job ID if applying
	 * @return int
	 */
	public static function get_job_id() {
		return absint( self::$job_id );
	}

	/**
	 * Get a field from either resume manager or job manager
	 */
	public static function get_field_template( $key, $field ) {
		switch ( $field['type'] ) {
			case 'education' :
			case 'experience' :
			case 'links' :
				get_job_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
			break;
			default :
				get_job_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field ) );
			break;
		}
	}

	/**
	 * init_fields function.
	 *
	 * @access public
	 * @return void
	 */
	public static function init_fields() {
		if ( self::$fields )
			return;

		self::$fields = apply_filters( 'submit_resume_form_fields', array(
			'resume_fields' => array(
				'candidate_name' => array(
					'label'       => __( 'Your name', 'wp-job-manager-resumes' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'Your full name', 'wp-job-manager-resumes' ),
					'priority'    => 1
				),
				'candidate_email' => array(
					'label'       => __( 'Your email', 'wp-job-manager-resumes' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'you@yourdomain.com', 'wp-job-manager-resumes' ),
					'priority'    => 2
				),
				'candidate_title' => array(
					'label'       => __( 'Professional title', 'wp-job-manager-resumes' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'e.g. "Web Developer"', 'wp-job-manager-resumes' ),
					'priority'    => 3
				),
				'candidate_location' => array(
					'label'       => __( 'Location', 'wp-job-manager-resumes' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'e.g. "London, UK", "New York", "Houston, TX"', 'wp-job-manager-resumes' ),
					'priority'    => 4
				),
				'candidate_photo' => array(
					'label'       => __( 'Photo', 'wp-job-manager-resumes' ),
					'type'        => 'file',
					'required'    => false,
					'placeholder' => '',
					'priority'    => 5,
					'allowed_mime_types' => array(
						'jpg' => 'image/jpeg',
						'gif' => 'image/gif',
						'png' => 'image/png'
					)
				),
				'candidate_video' => array(
					'label'       => __( 'Video', 'wp-job-manager-resumes' ),
					'type'        => 'text',
					'required'    => false,
					'priority'    => 6,
					'placeholder' => __( 'A link to a video about yourself', 'wp-job-manager-resumes' ),
				),
				'resume_category' => array(
					'label'       => __( 'Resume category', 'wp-job-manager-resumes' ),
					'type'        => 'term-multiselect',
					'taxonomy'    => 'resume_category',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 7
				),
				'resume_content' => array(
					'label'       => __( 'Resume Content', 'wp-job-manager-resumes' ),
					'type'        => 'wp-editor',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 8
				),
				'resume_skills' => array(
					'label'       => __( 'Skills', 'wp-job-manager-resumes' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'Comma separate a list of relevant skills', 'wp-job-manager-resumes' ),
					'priority'    => 9

				),
				'links' => array(
					'label'       => __( 'URL(s)', 'wp-job-manager-resumes' ),
					'type'        => 'links',
					'required'    => false,
					'placeholder' => '',
					'description' => __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-resumes' ),
					'priority'    => 10,
					'fields'      => array(
						'name' => array(
							'label'       => __( 'Name', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
							'priority'    => 1
						),
						'url' => array(
							'label'       => __( 'URL', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => 'http://',
							'priority'    => 2
						)
					)
				),
				'candidate_education' => array(
					'label'       => __( 'Education', 'wp-job-manager-resumes' ),
					'type'        => 'education',
					'required'    => false,
					'placeholder' => '',
					'priority'    => 11,
					'fields'      => array(
						'location' => array(
							'label'       => __( 'School name', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => ''
						),
						'qualification' => array(
							'label'       => __( 'Qualification(s)', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => ''
						),
						'date' => array(
							'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => ''
						),
						'notes' => array(
							'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
							'type'        => 'textarea',
							'required'    => false,
							'placeholder' => ''
						)
					)
				),
				'candidate_experience' => array(
					'label'       => __( 'Experience', 'wp-job-manager-resumes' ),
					'type'        => 'experience',
					'required'    => false,
					'placeholder' => '',
					'priority'    => 12,
					'fields'      => array(
						'employer' => array(
							'label'       => __( 'Employer', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => ''
						),
						'job_title' => array(
							'label'       => __( 'Job Title', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => ''
						),
						'date' => array(
							'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => ''
						),
						'notes' => array(
							'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
							'type'        => 'textarea',
							'required'    => false,
							'placeholder' => ''
						)
					)
				),
				'resume_file' => array(
					'label'       => __( 'Resume file', 'wp-job-manager-resumes' ),
					'type'        => 'file',
					'required'    => false,
					'description' => sprintf( __( 'Optionally upload your resume for employers to view. Max. file size: %s.', 'wp-job-manager-resumes' ), size_format( wp_max_upload_size() ) ),
					'priority'    => 13,
					'placeholder' => ''
				),
			)
		) );

		if ( ! get_option( 'resume_manager_enable_resume_upload' ) )
			unset( self::$fields['resume_fields']['resume_file'] );

		if ( ! get_option( 'resume_manager_enable_categories' ) || wp_count_terms( 'resume_category' ) == 0 )
			unset( self::$fields['resume_fields']['resume_category'] );

		if ( ! get_option( 'resume_manager_enable_skills' ) )
			unset( self::$fields['resume_fields']['resume_skills'] );
	}

	/**
	 * Get post data for fields
	 *
	 * @return array of data
	 */
	protected static function get_posted_fields() {

		self::init_fields();

		$values = array();

		foreach ( self::$fields as $group_key => $fields ) {
			foreach ( $fields as $key => $field ) {
				// Get the value
				$field_type = str_replace( '-', '_', $field['type'] );

				if ( method_exists( __CLASS__, "get_posted_{$field_type}_field" ) )
					$values[ $group_key ][ $key ] = call_user_func( __CLASS__ . "::get_posted_{$field_type}_field", $key, $field );
				else
					$values[ $group_key ][ $key ] = self::get_posted_field( $key, $field );

				// Set fields value
				self::$fields[ $group_key ][ $key ]['value'] = $values[ $group_key ][ $key ];
			}
		}

		return $values;
	}

	/**
	 * Get the value of a posted field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	protected static function get_posted_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( trim( stripslashes( $_POST[ $key ] ) ) ) : '';
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string|array
	 */
	protected static function get_posted_file_field( $key, $field ) {
		$file = self::upload_file( $key, $field );

		if ( ! $file ) {
			$file = self::get_posted_field( 'current_' . $key, $field );
		} elseif ( is_array( $file ) ) {
			$file = array_filter( array_merge( $file, (array) self::get_posted_field( 'current_' . $key, $field ) ) );
		}

		return $file;
	}

	/**
	 * Get the value of a posted textarea field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	protected static function get_posted_textarea_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? wp_kses_post( trim( stripslashes( $_POST[ $key ] ) ) ) : '';
	}

	/**
	 * Get the value of a posted textarea field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	protected static function get_posted_wp_editor_field( $key, $field ) {
		return self::get_posted_textarea_field( $key, $field );
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public static function get_posted_links_field( $key, $field ) {
		$name  = isset( $_POST['link_name'] ) ? $_POST['link_name'] : array();
		$url   = isset( $_POST['link_url'] ) ? $_POST['link_url'] : array();
		$links = array();

		for ( $i = 0; $i < sizeof( $name ); $i ++ ) {
			if ( ! empty( $name[ $i ] ) && ! empty( $url[ $i ] ) )
				$links[] = array(
					'name' => sanitize_text_field( stripslashes( $name[ $i ] ) ),
					'url'  => sanitize_text_field( stripslashes( $url[ $i ] ) )
				);
		}

		return apply_filters( 'submit_resume_form_fields_get_links_data', $links );
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public static function get_posted_education_field( $key, $field ) {
		$location      = isset( $_POST['candidate_education_location'] ) ? $_POST['candidate_education_location'] : array();
		$qualification = isset( $_POST['candidate_education_qualification'] ) ? $_POST['candidate_education_qualification'] : array();
		$date          = isset( $_POST['candidate_education_date'] ) ? $_POST['candidate_education_date'] : array();
		$notes         = isset( $_POST['candidate_education_notes'] ) ? $_POST['candidate_education_notes'] : array();
		$education     = array();

		for ( $i = 0; $i < sizeof( $location ); $i ++ ) {
			if ( ! empty( $location[ $i ] ) && ! empty( $qualification[ $i ] ) && ! empty( $date[ $i ] ) )
				$education[] = array(
					'location'      => sanitize_text_field( stripslashes( $location[ $i ] ) ),
					'qualification' => sanitize_text_field( stripslashes( $qualification[ $i ] ) ),
					'date'          => sanitize_text_field( stripslashes( $date[ $i ] ) ),
					'notes'         => wp_kses_post( stripslashes( $notes[ $i ] ) ),
				);
		}

		return apply_filters( 'submit_resume_form_fields_get_education_data', $education );
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public static function get_posted_experience_field( $key, $field ) {
		$employer   = isset( $_POST['candidate_experience_employer'] ) ? $_POST['candidate_experience_employer'] : array();
		$job_title  = isset( $_POST['candidate_experience_job_title'] ) ? $_POST['candidate_experience_job_title'] : array();
		$date       = isset( $_POST['candidate_experience_date'] ) ? $_POST['candidate_experience_date'] : array();
		$notes      = isset( $_POST['candidate_experience_notes'] ) ? $_POST['candidate_experience_notes'] : array();
		$experience = array();

		for ( $i = 0; $i < sizeof( $employer ); $i ++ ) {
			if ( ! empty( $employer[ $i ] ) && ! empty( $job_title[ $i ] ) && ! empty( $date[ $i ] ) )
				$experience[] = array(
					'employer'  => sanitize_text_field( stripslashes( $employer[ $i ] ) ),
					'job_title' => sanitize_text_field( stripslashes( $job_title[ $i ] ) ),
					'date'      => sanitize_text_field( stripslashes( $date[ $i ] ) ),
					'notes'     => wp_kses_post( stripslashes( $notes[ $i ] ) ),
				);
		}

		return apply_filters( 'submit_resume_form_fields_get_experience_data', $experience );
	}

	/**
	 * Get posted terms for the taxonomy
	 * @param  string $key
	 * @param  array $field
	 * @return array
	 */
	protected static function get_posted_term_checklist_field( $key, $field ) {
		if ( isset( $_POST[ 'tax_input' ] ) && isset( $_POST[ 'tax_input' ][ $field['taxonomy'] ] ) ) {
			// Ids were posted
			return array_map( 'absint', $_POST[ 'tax_input' ][ $field['taxonomy'] ] );
		} else {
			return array();
		}
	}

	/**
	 * Get posted terms for the taxonomy
	 * @param  string $key
	 * @param  array $field
	 * @return int
	 */
	protected static function get_posted_term_multiselect_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? array_map( 'absint', $_POST[ $key ] ) : array();
	}

	/**
	 * Get posted terms for the taxonomy
	 * @param  string $key
	 * @param  array $field
	 * @return int
	 */
	protected static function get_posted_term_select_field( $key, $field ) {
		return ! empty( $_POST[ $key ] ) && $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : '';
	}

	/**
	 * Validate the posted fields
	 *
	 * @return bool on success, WP_ERROR on failure
	 */
	protected static function validate_fields( $values ) {
		foreach ( self::$fields as $group_key => $fields ) {
			foreach ( $fields as $key => $field ) {
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-job-manager-resumes' ), $field['label'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						foreach ( $values[ $group_key ][ $key ] as $term ) {
							if ( ! term_exists( $term, $field['taxonomy'] ) ) {
								return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-resumes' ), $field['label'] ) );
							}
						}
					} elseif ( ! empty( $values[ $group_key ][ $key ] ) ) {
						if ( ! term_exists( $values[ $group_key ][ $key ], $field['taxonomy'] ) ) {
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-resumes' ), $field['label'] ) );
						}
					}
				}
			}
		}

		return apply_filters( 'submit_resume_form_validate_fields', true, self::$fields, $values );
	}

	/**
	 * get categories.
	 *
	 * @access private
	 * @return void
	 */
	private static function resume_categories() {
		$options = array();
		$terms   = get_resume_categories();
		foreach ( $terms as $term )
			$options[ $term->slug ] = $term->name;
		return $options;
	}

	/**
	 * Process function. all processing code if needed - can also change view if step is complete
	 */
	public static function process() {
		$keys = array_keys( self::$steps );

		if ( isset( $keys[ self::$step ] ) && is_callable( self::$steps[ $keys[ self::$step ] ]['handler'] ) ) {
			call_user_func( self::$steps[ $keys[ self::$step ] ]['handler'] );
		}
	}

	/**
	 * output function. Call the view handler.
	 */
	public static function output() {
		$keys = array_keys( self::$steps );

		self::show_errors();

		if ( isset( $keys[ self::$step ] ) && is_callable( self::$steps[ $keys[ self::$step ] ]['view'] ) ) {
			call_user_func( self::$steps[ $keys[ self::$step ] ]['view'] );
		}
	}

	/**
	 * Submit Step
	 */
	public static function submit() {
		global $job_manager, $post;

		self::init_fields();

		// Load data if neccessary
		if ( ! empty( $_POST['edit_resume'] ) && self::$resume_id ) {
			$resume = get_post( self::$resume_id );
			foreach ( self::$fields as $group_key => $fields ) {
				foreach ( $fields as $key => $field ) {
					switch ( $key ) {
						case 'candidate_name' :
							self::$fields[ $group_key ][ $key ]['value'] = $resume->post_title;
						break;
						case 'resume_content' :
							self::$fields[ $group_key ][ $key ]['value'] = $resume->post_content;
						break;
						case 'resume_skills' :
							self::$fields[ $group_key ][ $key ]['value'] = implode( ', ', wp_get_object_terms( $resume->ID, 'resume_skill', array( 'fields' => 'names' ) ) );
						break;
						case 'resume_category' :
							self::$fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $resume->ID, 'resume_category', array( 'fields' => 'ids' ) );
						break;
						default:
							self::$fields[ $group_key ][ $key ]['value'] = get_post_meta( $resume->ID, '_' . $key, true );
						break;
					}
				}
			}
			self::$fields = apply_filters( 'submit_resume_form_fields_get_resume_data', self::$fields, $resume );

		// Get user meta
		} elseif ( is_user_logged_in() && empty( $_POST ) ) {

			self::$fields = apply_filters( 'submit_resume_form_fields_get_user_data', self::$fields, get_current_user_id() );

		}

		get_job_manager_template( 'resume-submit.php', array(
			'class'              => __CLASS__,
			'form'               => self::$form_name,
			'resume_id'          => self::get_resume_id(),
			'job_id'             => self::get_job_id(),
			'action'             => self::get_action(),
			'resume_fields'      => self::get_fields( 'resume_fields' ),
			'step'               => self::get_step(),
			'submit_button_text' => apply_filters( 'submit_resume_form_submit_button_text', __( 'Preview &rarr;', 'wp-job-manager-resumes' ) )
		), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Submit Step is posted
	 */
	public static function submit_handler() {
		try {

			// Init fields
			self::init_fields();

			// Get posted values
			$values = self::get_posted_fields();

			if ( empty( $_POST['submit_resume'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) )
				return;

			// Validate required
			if ( is_wp_error( ( $return = self::validate_fields( $values ) ) ) )
				throw new Exception( $return->get_error_message() );

			// Account creation
			if ( ! is_user_logged_in() ) {
				$create_account = false;

				if ( resume_manager_enable_registration() && ! empty( $_POST['candidate_email'] ) ) {
					$create_account = wp_job_manager_create_account( $_POST['candidate_email'], get_option( 'resume_manager_registration_role', 'candidate' ) );
				}

				if ( is_wp_error( $create_account ) ) {
					throw new Exception( $create_account->get_error_message() );
				}
			}

			if ( resume_manager_user_requires_account() && ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to post your resume.', 'wp-job-manager-resumes' ) );
			}

			// Update the job
			self::save_resume( $values['resume_fields']['candidate_name'], $values['resume_fields']['resume_content'], self::$resume_id ? '' : 'preview', $values );
			self::update_resume_data( $values );

			// Successful, show next step
			self::$step ++;

		} catch ( Exception $e ) {
			self::add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Update or create a job listing from posted data
	 *
	 * @param  string $post_title
	 * @param  string $post_content
	 * @param  string $status
	 */
	protected static function save_resume( $post_title, $post_content, $status = 'preview', $values = array() ) {
		$resume_slug   = array();

		// Prepend with unqiue key
		if ( self::$resume_id ) {
			$prefix = get_post_meta( self::$resume_id, '_resume_name_prefix', true );

			if ( ! $prefix )
				$prefix = wp_generate_password( 10 );

			$resume_slug[] = $prefix;
		} else {
			$prefix        = wp_generate_password( 10 );
			$resume_slug[] = $prefix;
		}
		$resume_slug[] = $post_title;

		$data = apply_filters( 'submit_resume_form_save_resume_data', array(
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'resume',
			'comment_status' => 'closed',
			'post_password'  => '',
			'post_name'      => sanitize_title( implode( '-', $resume_slug ) ),
		), $post_title, $post_content, $status, $values );

		if ( $status ) {
			$data['post_status'] = $status;
		}

		if ( self::$resume_id ) {
			$data['ID'] = self::$resume_id;
			wp_update_post( $data );
		} else {
			self::$resume_id = wp_insert_post( $data );
			update_post_meta( self::$resume_id, '_resume_name_prefix', $prefix );

			// Save profile fields
			$current_user   = wp_get_current_user();
			$candidate_name = explode( ' ', $post_title );

			if ( empty( $current_user->first_name ) && empty( $current_user->last_name ) && sizeof( $candidate_name ) > 1 ) {
				wp_update_user(
					array(
						'ID'         => $current_user->ID,
						'first_name' => current( $candidate_name ),
						'last_name'  => end( $candidate_name )
					)
				);
			}
		}
	}

	/**
	 * Set job meta + terms based on posted values
	 *
	 * @param  array $values
	 */
	protected static function update_resume_data( $values ) {
		// Set defaults
		add_post_meta( self::$resume_id, '_featured', 0, true );

		// Loop fields and save meta and term data
		foreach ( self::$fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Save taxonomies
				if ( ! empty( $field['taxonomy'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( self::$resume_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( self::$resume_id, array( $values[ $group_key ][ $key ] ), $field['taxonomy'], false );
					}

				// Save meta data
				} else {
					update_post_meta( self::$resume_id, '_' . $key, $values[ $group_key ][ $key ] );
				}
			}
		}

		if ( get_option( 'resume_manager_enable_skills' ) && isset( $values['resume_fields']['resume_skills'] ) ) {

			$tags     = array();
			$raw_tags = $values['resume_fields']['resume_skills'];

			if ( is_string( $raw_tags ) ) {
				// Explode and clean
				$raw_tags = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_tags ) ) );

				if ( ! empty( $raw_tags ) ) {
					foreach ( $raw_tags as $tag ) {
						// We'll assume that small tags less than or equal to 3 chars are abbreviated. Uppercase them.
						if ( strlen( $tag ) <= 3 ) {
							$tag = strtoupper( $tag );
						} else {
							$tag = strtolower( $tag );
						}

						if ( $term = get_term_by( 'name', $tag, 'resume_skill' ) ) {
							$tags[] = $term->term_id;
						} else {
							$term = wp_insert_term( $tag, 'resume_skill' );

							if ( ! is_wp_error( $term ) ) {
								$tags[] = $term['term_id'];
							}
						}
					}
				}
			} else {
				$tags = array_map( 'absint', $raw_tags );
			}

			wp_set_object_terms( self::$resume_id, $tags, 'resume_skill', false );
		}

		do_action( 'resume_manager_update_resume_data', self::$resume_id, $values );
	}

	/**
	 * Preview Step
	 */
	public static function preview() {
		global $post, $resume_preview;

		wp_enqueue_script( 'wp-resume-manager-resume-submission' );

		if ( self::$resume_id ) {

			$resume_preview = true;
			$post = get_post( self::$resume_id );
			setup_postdata( $post );
			?>
			<form method="post" id="resume_preview">
				<div class="resume_preview_title">
					<input type="submit" name="continue" id="resume_preview_submit_button" class="button" value="<?php echo apply_filters( 'submit_resume_step_preview_submit_text', __( 'Submit Resume &rarr;', 'wp-job-manager-resumes' ) ); ?>" />
					<input type="submit" name="edit_resume" class="button" value="<?php _e( '&larr; Edit resume', 'wp-job-manager-resumes' ); ?>" />
					<input type="hidden" name="resume_id" value="<?php echo esc_attr( self::$resume_id ); ?>" />
					<input type="hidden" name="job_id" value="<?php echo esc_attr( self::$job_id ); ?>" />
					<input type="hidden" name="step" value="<?php echo esc_attr( self::$step ); ?>" />
					<input type="hidden" name="resume_manager_form" value="<?php echo self::$form_name; ?>" />
					<h2>
						<?php _e( 'Preview', 'wp-job-manager-resumes' ); ?>
					</h2>
				</div>
				<div class="resume_preview single-resume">
					<h1><?php the_title(); ?></h1>
					<?php get_job_manager_template_part( 'content-single', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>
				</div>
			</form>
			<?php

			wp_reset_postdata();
		}
	}

	/**
	 * Preview Step Form handler
	 */
	public static function preview_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_resume'] ) ) {
			self::$step --;
		}

		// Continue = change job status then show next screen
		if ( ! empty( $_POST['continue'] ) ) {

			$resume = get_post( self::$resume_id );

			if ( in_array( $resume->post_status, array( 'preview', 'expired' ) ) ) {

				// Reset expirey
				delete_post_meta( $resume->ID, '_resume_expires' );

				// Update listing
				$update_resume                = array();
				$update_resume['ID']          = $resume->ID;
				$update_resume['post_status'] = get_option( 'resume_manager_submission_requires_approval' ) ? 'pending' : 'publish';
				wp_update_post( $update_resume );
			}

			self::$step ++;
		}
	}

	/**
	 * Done Step
	 */
	public static function done() {
		do_action( 'resume_manager_resume_submitted', self::$resume_id );

		get_job_manager_template( 'resume-submitted.php', array( 'resume' => get_post( self::$resume_id ), 'job_id' => self::$job_id ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );

		if ( self::$job_id && get_option( 'resume_manager_enable_application' ) ) {
			if ( get_post_type( self::$job_id ) !== 'job_listing' ) {
				return;
			}

			$method = get_the_job_application_method( self::$job_id );

			if ( "email" !== $method->type && ! class_exists( 'WP_Job_Manager_Applications' ) ) {
				return;
			}
			?>
			<form method="post" class="apply_with_resume">
				<p class="applying_for"><?php printf( __( 'Enter a message below to apply to "%s". This will accompany your online resume and be sent to the employer.', 'wp-job-manager-resumes' ), '<a href="' . get_permalink( self::$job_id ) . '">' . get_the_title( self::$job_id ) . '</a>' ); ?></p>
				<p>
					<label><?php _e( 'Message', 'wp-job-manager-resumes' ); ?>:</label>
					<textarea name="application_message" cols="20" rows="4" required><?php if ( isset( $_POST['application_message'] ) ) echo esc_textarea( stripslashes( $_POST['application_message'] ) ); ?></textarea>
				</p>
				<p>
					<input type="submit" name="resume_application_submit_button" value="<?php esc_attr_e( 'Send application', 'wp-job-manager-resumes' ); ?>" />
					<input type="hidden" name="resume_id" value="<?php echo esc_attr( self::$resume_id ); ?>" />
					<input type="hidden" name="job_id" value="<?php echo esc_attr( self::$job_id ); ?>" />
					<input type="hidden" name="step" value="<?php echo esc_attr( self::$step ); ?>" />
					<input type="hidden" name="resume_manager_form" value="<?php echo self::$form_name; ?>" />
				</p>
			</form>
			<?php
		}
	}

	/**
	 * Optional step triggered when applying to a job after submitting a resume
	 */
	public static function application_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Continue = change job status then show next screen
		if ( ! empty( $_POST['resume_application_submit_button'] ) ) {

			$application_message = wp_kses_post( stripslashes( $_POST['application_message'] ) );

			if ( ! $application_message ) {
				self::add_error( __( 'Please enter a message', 'wp-job-manager-resumes' ) );
				return;
			}

			if ( WP_Resume_Manager_Apply::send_application( self::$job_id, self::$resume_id, $application_message ) ) {
				self::$step ++;
			} else {
				self::add_error( __( 'Error sending application.', 'wp-job-manager-resumes' ) );
				return;
			}
		}
	}

	/**
	 * Show message once the application has been sent
	 */
	public static function application_done() {
		printf( '<p>' . __( 'Your application has been sent successfully', 'wp-job-manager-resumes' ) . '</p>' );
	}

	/**
	 * Upload a file
	 */
	public static function upload_file( $field_key, $field ) {
		if ( 'resume_file' == $field_key ) {
			add_filter( 'upload_dir', array( __CLASS__, 'upload_resume_file_dir' ), 20 );
		} else {
			remove_filter( 'upload_dir', array( __CLASS__, 'upload_resume_file_dir' ), 20 );
		}

		/** WordPress Administration File API */
		include_once( ABSPATH . 'wp-admin/includes/file.php' );

		/** WordPress Media Administration API */
		include_once( ABSPATH . 'wp-admin/includes/media.php' );

		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) {
			$file = $_FILES[ $field_key ];

			if ( ! empty( $field['allowed_mime_types'] ) ) {
				$allowed_mime_types = $field['allowed_mime_types'];
			} else {
				$allowed_mime_types = get_allowed_mime_types();
			}

			if ( empty( $file['name'] ) ) {
				return false;
			}

			if ( is_array( $file['name'] ) ) {
				$file_urls = array();

				foreach ( $file['name'] as $key => $value ) {
					if ( ! empty( $file['name'][ $key ] ) ) {

						if ( ! in_array( $file['type'][ $key ], $allowed_mime_types ) ) {
			    			throw new Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-job-manager' ), $field['label'], $file['type'][ $key ], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
						}

						$upload_file = array(
							'name'     => $file['name'][ $key ],
							'type'     => $file['type'][ $key ],
							'tmp_name' => $file['tmp_name'][ $key ],
							'error'    => $file['error'][ $key ],
							'size'     => $file['size'][ $key ]
						);

						add_filter( 'upload_dir',  array( __CLASS__, 'upload_dir' ) );
						$upload = wp_handle_upload( $upload_file, apply_filters( 'submit_resume_wp_handle_upload_overrides', array( 'test_form' => FALSE ) ) );
						remove_filter( 'upload_dir', array( __CLASS__, 'upload_dir' ) );

						if ( ! empty( $upload['error'] ) ) {
							throw new Exception( $upload['error'] );
						}

						$file_urls[] = $upload['url'];
					}
				}

				return $file_urls;
			} else {
				if ( ! in_array( $file['type'], $allowed_mime_types ) ) {
	    			throw new Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-job-manager' ), $field['label'], $file['type'], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
				}

				add_filter( 'upload_dir',  array( __CLASS__, 'upload_dir' ) );
				$upload = wp_handle_upload( $file, apply_filters( 'submit_resume_wp_handle_upload_overrides', array( 'test_form' => FALSE ) ) );
				remove_filter( 'upload_dir', array( __CLASS__, 'upload_dir' ) );

				if ( ! empty( $upload['error'] ) ) {
					throw new Exception( $upload['error'] );
				} else {
					return $upload['url'];
				}
			}
		}
	}

	/**
	 * Filter the upload directory
	 */
	public static function upload_dir( $pathdata ) {
		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['path']   = $pathdata['path'] . '/resumes';
			$pathdata['url']    = $pathdata['url']. '/resumes';
			$pathdata['subdir'] = '/resumes';
		} else {
			$new_subdir         = '/resumes' . $pathdata['subdir'];
			$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
		}
		return $pathdata;
	}

	/**
	 * Filter the upload directory
	 */
	public static function upload_resume_file_dir( $pathdata ) {
		$pathdata['path']   = str_replace( '/resumes', '/resumes/resume_files', $pathdata['path'] );
		$pathdata['url']    = str_replace( '/resumes', '/resumes/resume_files', $pathdata['url'] );
		$pathdata['subdir'] = str_replace( '/resumes', '/resumes/resume_files', $pathdata['subdir'] );
		return $pathdata;
	}
}