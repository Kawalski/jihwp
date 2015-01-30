<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * WP_Job_Manager_Applications_Apply class.
 */
class WP_Job_Manager_Applications_Apply {

	private $fields  = array();
	private $error   = "";
	private $message = "";

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'sanitize_file_name_chars', array( $this, 'sanitize_file_name_chars' ) );
		add_action( 'init', array( $this, 'init' ), 20 );
		add_action( 'wp', array( $this, 'application_form_handler' ) );
	}

	/**
	 * Chars which should be removed from file names
	 */
	public function sanitize_file_name_chars( $chars ) {
		$chars[] = "%";
		$chars[] = "^";
		return $chars;
	}

	/**
	 * Init application form
	 */
	public function init() {
		global $job_manager, $resume_manager;

		if ( ! is_admin() ) {
			if ( get_option( 'job_application_form_for_email_method', '1' ) ) {
				add_action( 'job_manager_application_details_email', array( $this, 'application_form' ), 20 );

				// Unhook job manager apply details
				remove_action( 'job_manager_application_details_email', array( $job_manager->post_types, 'application_details_email' ) );

				// Unhook resume managers form
				if ( $resume_manager && ! empty( $resume_manager->apply ) ) {
					remove_action( 'job_manager_application_details_email', array( $resume_manager->apply, 'apply_with_resume' ), 20 );
				}
			}
			if ( get_option( 'job_application_form_for_url_method', '1' ) ) {
				add_action( 'job_manager_application_details_url', array( $this, 'application_form' ), 20 );

				// Unhook job manager apply details
				remove_action( 'job_manager_application_details_url', array( $job_manager->post_types, 'application_details_url' ) );
			}
		}
	}

	/**
	 * Init form fields
	 */
	public function init_fields() {
		if ( ! empty( $this->fields ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			// Resume manager integration
			if ( function_exists( 'get_resume_share_link' ) ) {
				$args = apply_filters( 'resume_manager_get_application_form_resumes_args', array(
					'post_type'           => 'resume',
					'post_status'         => array( 'publish', 'pending' ),
					'ignore_sticky_posts' => 1,
					'posts_per_page'      => -1,
					'orderby'             => 'date',
					'order'               => 'desc',
					'author'              => get_current_user_id()
				) );

				$resumes      = array();
				$resume_posts = get_posts( $args );
				foreach ( $resume_posts as $resume ) {
					$resumes[ $resume->ID ] = $resume->post_title;
				}
			} else {
				$resumes         = null;
			}
			$user            = wp_get_current_user();
			$candidate_name  = $user->first_name . ' ' . $user->last_name;
			$candidate_email = $user->user_email;
		} else {
			$resumes         = null;
			$candidate_name  = '';
			$candidate_email = '';
		}

		$this->fields = array(
			'candidate_name' => array(
				'label'       => __( 'Full name', 'wp-job-manager-applications' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => '',
				'priority'    => 1,
				'value'       => isset( $_POST['candidate_name'] ) ? sanitize_text_field( $_POST['candidate_name'] ) : $candidate_name
			),
			'candidate_email' => array(
				'label'       => __( 'Email address', 'wp-job-manager-applications' ),
				'description' => '',
				'type'        => 'text',
				'required'    => true,
				'placeholder' => '',
				'priority'    => 2,
				'value'       => isset( $_POST['candidate_email'] ) ? sanitize_text_field( $_POST['candidate_email'] ) : $candidate_email
			),
			'application_message' => array(
				'label'       => __( 'Message', 'wp-job-manager-applications' ),
				'type'        => 'textarea',
				'required'    => true,
				'placeholder' => __( 'Your cover letter/message sent to the employer', 'wp-job-manager-applications' ),
				'priority'    => 3,
				'value'       => isset( $_POST['application_message'] ) ? str_replace( '[nl]', "\n", sanitize_text_field( str_replace( "\n", '[nl]', strip_tags( stripslashes( $_POST['application_message'] ) ) ) ) ) : ''
			),
			'application_attachment' => array(
				'label'       => __( 'Upload CV', 'wp-job-manager-applications' ),
				'type'        => 'file',
				'required'    => true,
				'priority'    => 5,
				'placeholder' => '',
				'multiple'    => true,
				'description' => sprintf( __( 'Upload your CV/resume or any other relevant file. Max. file size: %s.', 'wp-job-manager-applications' ), size_format( wp_max_upload_size() ) )
			)
		);

		if ( sizeof( $resumes ) ) {
			$this->fields['resume_id'] = array(
				'label'       => __( 'Online Resume', 'wp-job-manager-applications' ),
				'description' => '',
				'type'        => 'select',
				'required'    => false,
				'options'     => array( '0' => __( 'N/A', 'wp-job-manager-applications' ) ) + $resumes,
				'priority'    => 4
			);
			if ( isset( $this->fields['application_attachment'] ) ) {
				$this->fields['application_attachment']['required'] = false;
			}
		}

		$this->fields = apply_filters( 'job_application_form_fields', $this->fields );

		uasort( $this->fields, array( $this, 'sort_by_priority' ) );
	}

	/**
	 * Allow users to apply to a job with a resume
	 */
	public function application_form() {
		if ( get_option( 'job_application_form_require_login', 0 ) && ! is_user_logged_in() ) {
			get_job_manager_template( 'application-form-login.php', array(), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
		} else {
			$this->init_fields();

			// Application form
			get_job_manager_template( 'application-form.php', array( 'application_fields' => $this->fields ), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
		}
	}

	/**
	 * Sort array by priority value
	 */
	private function sort_by_priority( $a, $b ) {
		return $a['priority'] - $b['priority'];
	}

	/**
	 * Send the application email if posted
	 */
	public function application_form_handler() {
		if ( ! empty( $_POST['wp_job_manager_send_application'] ) ) {
			$this->init_fields();

			add_action( 'job_content_start', array( $this, 'application_form_result' ) );

			try {
				$values = array();
				$job_id = absint( $_POST['job_id'] );
				$job    = get_post( $job_id );
				$meta   = array();

				if ( empty( $job_id ) || ! $job || 'job_listing' !== $job->post_type ) {
					throw new Exception( __( 'Invalid job', 'wp-job-manager-applications' ) );
				}

				// Validate posted fields
				foreach ( $this->fields as $key => $field ) {
					// Get fields
					switch( $field['type'] ) {
						case "textarea" :
							$values[ $key ] = isset( $_POST[ $key ] ) ? str_replace( '[nl]', "\n", sanitize_text_field( str_replace( "\n", '[nl]', strip_tags( stripslashes( $_POST[ $key ] ) ) ) ) ) : '';
						break;
						case "file" :
							$values[ $key ] = $this->upload_file( $key, $field );
						break;
						case "multiselect" :
							$values[ $key ] = isset( $_POST[ $key ] ) ? array_map( 'sanitize_text_field', $_POST[ $key ] ) : '';
						break;
						default :
							$values[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( $_POST[ $key ] ) : '';
						break;
					}

					// Validate required
					if ( $field['required'] && empty( $values[ $key ] ) ) {
						throw new Exception( sprintf( __( '"%s" is a required field', 'wp-job-manager' ), $field['label'] ) );
					}

					// Errprs
					if ( is_wp_error( $values[ $key ] ) ) {
						throw new Exception( $field['label'] . ': ' . $values[ $key ]->get_error_message() );
					}

					// Extra validation rules
					switch( $key ) {
						case 'candidate_email' :
							if ( empty( $values[ $key ] ) || ! is_email( $values[ $key ] ) ) {
								throw new Exception( __( 'Please provide a valid email address', 'wp-job-manager-applications' ) );
							}
						break;
					}
				}

				// Validation hook
				$valid = apply_filters( 'application_form_validate_fields', true, $this->fields, $values );

				if ( is_wp_error( $valid ) ) {
					throw new Exception( $valid->get_error_message() );
				}

				// Prepare meta data to save
				if ( ! empty( $values[ 'application_attachment' ] ) ) {
					foreach ( $values[ 'application_attachment' ] as $attachment ) {
						if ( ! is_wp_error( $attachment ) ) {
							if ( 1 === sizeof( $values[ 'application_attachment' ] ) ) {
								$meta['_attachment']      = $attachment['url'];
								$meta['_attachment_file'] = $attachment['file'];
							} else {
								$meta['_attachment'][]      = $attachment['url'];
								$meta['_attachment_file'][] = $attachment['file'];
							}
						}
					}
				}

				if ( ! empty( $values['resume_id'] ) && function_exists( 'get_resume_share_link' ) ) {
					$meta['_resume_id'] = $values['resume_id'];
				}

				// Filter meta
				$meta = apply_filters( 'job_application_form_posted_meta', $meta, $values );

				// Create application
				if ( ! $application_id = create_job_application( $job_id, $values['candidate_name'], $values['candidate_email'], $values['application_message'], $meta ) ) {
					throw new Exception( __( 'Could not create job application', 'wp-job-manager-applications' ) );
				}

				// Message to display
				$this->message = __( 'Your job application has been submitted successfully', 'wp-job-manager-applications' );

				// Trigger action
				do_action( 'new_job_application', $application_id, $job_id );

			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
			}
		}
	}

	/**
	 * Upload a file
	 *
	 * @return  array
	 */
	public function upload_file( $field_key, $field ) {
		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) {
			include_once( ABSPATH . 'wp-admin/includes/file.php' );
			include_once( ABSPATH . 'wp-admin/includes/media.php' );

			$file               = $_FILES[ $field_key ];

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

						add_filter( 'upload_dir',  array( $this, 'upload_dir' ) );
						$upload = wp_handle_upload( $upload_file, array( 'test_form' => false ) );
						remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );

						if ( ! empty( $upload['error'] ) ) {
							throw new Exception( $upload['error'] );
						}

						$file_urls[] = $upload;
					}
				}

				return $file_urls;
			} else {
				if ( ! in_array( $file['type'], $allowed_mime_types ) ) {
	    			throw new Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-job-manager' ), $field['label'], $file['type'], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
				}

				add_filter( 'upload_dir',  array( $this, 'upload_dir' ) );
				$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
				remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );

				if ( ! empty( $upload['error'] ) ) {
					throw new Exception( $upload['error'] );
				} else {
					return array( $upload );
				}
			}
		}
	}

	/**
	 * Filter the upload directory
	 */
	public static function upload_dir( $pathdata ) {
		$secret_dir = uniqid();

		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['path']   = $pathdata['path'] . '/job_applications/' . $secret_dir;
			$pathdata['url']    = $pathdata['url']. '/job_applications/' . $secret_dir;
			$pathdata['subdir'] = '/job_applications/' . $secret_dir;
		} else {
			$new_subdir         = '/job_applications/' . $secret_dir;
			$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
		}
		return $pathdata;
	}

	/**
	 * Show results - errors and messages
	 */
	public function application_form_result() {
		if ( $this->message ) {
			echo '<p class="job-manager-message">' . esc_html( $this->message ) . '</p>';
		} elseif ( $this->error ) {
			echo '<p class="job-manager-error">' . esc_html( $this->error ) . '</p>';
		}
	}
}

new WP_Job_Manager_Applications_Apply();