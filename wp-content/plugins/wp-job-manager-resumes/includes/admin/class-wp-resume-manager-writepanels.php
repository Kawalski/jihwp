<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'WP_Job_Manager_Writepanels' ) ) {
	include( JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-writepanels.php' );
}

class WP_Resume_Manager_Writepanels extends WP_Job_Manager_Writepanels {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
		add_action( 'resume_manager_save_resume', array( $this, 'save_resume_data' ), 1, 2 );
	}

	/**
	 * Resume fields
	 *
	 * @return array
	 */
	public function resume_fields() {
		$fields = apply_filters( 'resume_manager_resume_fields', array(
			'_candidate_title' => array(
				'label'       => __( 'Professional title', 'wp-job-manager-resumes' ),
				'placeholder' => '',
				'description' => ''
			),
			'_candidate_email' => array(
				'label'       => __( 'Contact email', 'wp-job-manager-resumes' ),
				'placeholder' => __( 'you@yourdomain.com', 'wp-job-manager-resumes' ),
				'description' => ''
			),
			'_candidate_location' => array(
				'label'       => __( 'Candidate location', 'wp-job-manager-resumes' ),
				'placeholder' => __( 'e.g. "London, UK", "New York", "Houston, TX"', 'wp-job-manager-resumes' ),
				'description' => ''
			),
			'_candidate_photo' => array(
				'label'       => __( 'Photo', 'wp-job-manager-resumes' ),
				'placeholder' => __( 'URL to the candidate photo', 'wp-job-manager-resumes' ),
				'type'        => 'file'
			),
			'_candidate_video' => array(
				'label'       => __( 'Video', 'wp-job-manager-resumes' ),
				'placeholder' => __( 'URL to the candidate video', 'wp-job-manager-resumes' ),
				'type'        => 'text'
			),
			'_resume_file' => array(
				'label'       => __( 'Resume file', 'wp-job-manager-resumes' ),
				'placeholder' => __( 'URL to the candidate\'s resume file', 'wp-job-manager-resumes' ),
				'type'        => 'file'
			),
			'_resume_author' => array(
				'label' => __( 'Posted by', 'wp-job-manager-resumes' ),
				'type'  => 'author'
			),
			'_featured' => array(
				'label' => __( 'Feature this resume?', 'wp-job-manager-resumes' ),
				'type'  => 'checkbox',
				'description' => __( 'Featured resumes will be sticky during searches, and can be styled differently.', 'wp-job-manager-resumes' )
			),
			'_resume_expires' => array(
				'label'       => __( 'Expires', 'wp-job-manager-resumes' ),
				'placeholder' => __( 'yyyy-mm-dd', 'wp-job-manager-resumes' )
			),
		) );

		if ( ! get_option( 'resume_manager_enable_resume_upload' ) )
			unset( $fields['_resume_file'] );

		return $fields;
	}

	/**
	 * add_meta_boxes function.
	 */
	public function add_meta_boxes() {
		add_meta_box( 'resume_data', __( 'Candidate Data', 'wp-job-manager-resumes' ), array( $this, 'resume_data' ), 'resume', 'normal', 'high' );
		add_meta_box( 'resume_url_data', __( 'URL(s)', 'wp-job-manager-resumes' ), array( $this, 'url_data' ), 'resume', 'side', 'low' );
		add_meta_box( 'resume_education_data', __( 'Education', 'wp-job-manager-resumes' ), array( $this, 'education_data' ), 'resume', 'normal', 'high' );
		add_meta_box( 'resume_experience_data', __( 'Experience', 'wp-job-manager-resumes' ), array( $this, 'experience_data' ), 'resume', 'normal', 'high' );
	}

	/**
	 * Resume data
	 *
	 * @param mixed $post
	 */
	public function resume_data( $post ) {
		global $post, $thepostid;

		$thepostid = $post->ID;

		echo '<div class="wp_resume_manager_meta_data wp_job_manager_meta_data">';

		wp_nonce_field( 'save_meta_data', 'resume_manager_nonce' );

		do_action( 'resume_manager_resume_data_start', $thepostid );

		foreach ( $this->resume_fields() as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';

			if ( method_exists( $this, 'input_' . $type ) )
				call_user_func( array( $this, 'input_' . $type ), $key, $field );
			else
				do_action( 'resume_manager_input_' . $type, $key, $field );
		}

		do_action( 'resume_manager_resume_data_end', $thepostid );

		echo '</div>';
	}

	/**
	 * Resume URL data
	 *
	 * @param mixed $post
	 */
	public function url_data( $post ) {
		echo '<p>' . __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-resumes' ) . '</p>';
		?>
		<table>
			<thead>
				<tr>
					<th class="left"><label><?php _e( 'Name', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'URL', 'wp-job-manager-resumes' ); ?></label></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2">
						<div class="submit">
							<input type="submit" class="button resume_manager_add_row" value="<?php _e( 'Add URL', 'wp-job-manager-resumes' ); ?>" data-row="<?php
								ob_start();
								$name = $url = '';
								include( 'views/html-url-row.php' );
								echo esc_attr( ob_get_clean() );
							?>" />
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
					$items = get_post_meta( $post->ID, '_links', true );

					if ( $items ) {
						foreach ( $items as $item ) {
							$name = $item['name'];
							$url  = $item['url'];
							include( 'views/html-url-row.php' );
						}
					}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Resume Education data
	 *
	 * @param mixed $post
	 */
	public function education_data( $post ) {
		?>
		<table>
			<thead>
				<tr>
					<th class="left"><label><?php _e( 'School name', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'Qualification(s)', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'Start/end date', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'Notes', 'wp-job-manager-resumes' ); ?></label></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
						<div class="submit">
							<input type="submit" class="button resume_manager_add_row" value="<?php _e( 'Add Education', 'wp-job-manager-resumes' ); ?>" data-row="<?php
								ob_start();
								$location = $date = $qualification = $notes = '';
								include( 'views/html-education-row.php' );
								echo esc_attr( ob_get_clean() );
							?>" />
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
					$items = get_post_meta( $post->ID, '_candidate_education', true );

					if ( $items ) {
						foreach ( $items as $item ) {
							$location      = $item['location'];
							$date          = $item['date'];
							$qualification = $item['qualification'];
							$notes         = $item['notes'];
							include( 'views/html-education-row.php' );
						}
					}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Resume Education data
	 *
	 * @param mixed $post
	 */
	public function experience_data( $post ) {
		?>
		<table>
			<thead>
				<tr>
					<th class="left"><label><?php _e( 'Employer', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'Job Title', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'Start/end date', 'wp-job-manager-resumes' ); ?></label></th>
					<th><label><?php _e( 'Notes', 'wp-job-manager-resumes' ); ?></label></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
						<div class="submit">
							<input type="submit" class="button resume_manager_add_row" value="<?php _e( 'Add Experience', 'wp-job-manager-resumes' ); ?>" data-row="<?php
								ob_start();
								$employer = $date = $job_title = $notes = '';
								include( 'views/html-experience-row.php' );
								echo esc_attr( ob_get_clean() );
							?>" />
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
					$items = get_post_meta( $post->ID, '_candidate_experience', true );

					if ( $items ) {
						foreach ( $items as $item ) {
							$employer  = $item['employer'];
							$date      = $item['date'];
							$job_title = $item['job_title'];
							$notes     = $item['notes'];
							include( 'views/html-experience-row.php' );
						}
					}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Triggered on Save Post
	 *
	 * @param mixed $post_id
	 * @param mixed $post
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( is_int( wp_is_post_revision( $post ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post ) ) ) return;
		if ( empty( $_POST['resume_manager_nonce'] ) || ! wp_verify_nonce( $_POST['resume_manager_nonce'], 'save_meta_data' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( $post->post_type != 'resume' ) return;

		do_action( 'resume_manager_save_resume', $post_id, $post );
	}

	/**
	 * Save Resume Meta
	 *
	 * @param mixed $post_id
	 * @param mixed $post
	 */
	public function save_resume_data( $post_id, $post ) {
		global $wpdb;

		// These need to exist
		add_post_meta( $post_id, '_featured', 0, true );

		foreach ( $this->resume_fields() as $key => $field ) {

			// Expirey date
			if ( '_resume_expires' === $key ) {
				if ( ! empty( $_POST[ $key ] ) ) {
					update_post_meta( $post_id, $key, date( 'Y-m-d', strtotime( sanitize_text_field( $_POST[ $key ] ) ) ) );
				} else {
					update_post_meta( $post_id, $key, '' );
				}
			}

			elseif ( '_candidate_location' === $key ) {
				if ( update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) ) ) {
					do_action( 'resume_manager_candidate_location_edited', $post_id, sanitize_text_field( $_POST[ $key ] ) );
				} elseif ( apply_filters( 'resume_manager_geolocation_enabled', true ) && ! WP_Job_Manager_Geocode::has_location_data( $post_id ) ) {
					WP_Job_Manager_Geocode::generate_location_data( $post_id, sanitize_text_field( $_POST[ $key ] ) );
				}
				continue;
			}

			elseif( '_resume_author' === $key ) {
				$wpdb->update( $wpdb->posts, array( 'post_author' => $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : 0 ), array( 'ID' => $post_id ) );
			}

			// Everything else
			else {
				$type = ! empty( $field['type'] ) ? $field['type'] : '';

				switch ( $type ) {
					case 'textarea' :
						update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) );
					break;
					case 'checkbox' :
						if ( isset( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, 1 );
						} else {
							update_post_meta( $post_id, $key, 0 );
						}
					break;
					default :
						if ( is_array( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) );
						} else {
							update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
						}
					break;
				}
			}
		}

		// Education
		$candidate_education = array();
		$locations           = isset( $_POST['resume_education_location'] ) ? $_POST['resume_education_location'] : array();
		$qualifications      = isset( $_POST['resume_education_qualification'] ) ? $_POST['resume_education_qualification'] : array();
		$dates               = isset( $_POST['resume_education_date'] ) ? $_POST['resume_education_date'] : array();
		$notes               = isset( $_POST['resume_education_notes'] ) ? $_POST['resume_education_notes'] : array();

		foreach ( $locations as $index => $location ) {
			if ( ! empty( $location ) && ! empty( $qualifications[ $index ] ) && ! empty( $dates[ $index ] ) ) {
				$candidate_education[] = array(
					'location'      => sanitize_text_field( stripslashes( $location ) ),
					'qualification' => sanitize_text_field( stripslashes( $qualifications[ $index ] ) ),
					'date'          => sanitize_text_field( stripslashes( $dates[ $index ] ) ),
					'notes'         => wp_kses_post( stripslashes( $notes[ $index ] ) )
				);
			}
		}

		update_post_meta( $post_id, '_candidate_education', $candidate_education );

		// Education
		$candidate_experience = array();
		$employers            = isset( $_POST['resume_experience_employer'] ) ? $_POST['resume_experience_employer'] : array();
		$job_titles           = isset( $_POST['resume_experience_job_title'] ) ? $_POST['resume_experience_job_title'] : array();
		$dates                = isset( $_POST['resume_experience_date'] ) ? $_POST['resume_experience_date'] : array();
		$notes                = isset( $_POST['resume_experience_notes'] ) ? $_POST['resume_experience_notes'] : array();

		foreach ( $employers as $index => $employer ) {
			if ( ! empty( $employer ) && ! empty( $job_titles[ $index ] ) && ! empty( $dates[ $index ] ) ) {
				$candidate_experience[] = array(
					'employer'  => sanitize_text_field( stripslashes( $employer ) ),
					'job_title' => sanitize_text_field( stripslashes( $job_titles[ $index ] ) ),
					'date'      => sanitize_text_field( stripslashes( $dates[ $index ] ) ),
					'notes'     => wp_kses_post( stripslashes( $notes[ $index ] ) )
				);
			}
		}

		update_post_meta( $post_id, '_candidate_experience', $candidate_experience );

		// URLS
		$links            = array();
		$resume_url_names = isset( $_POST['resume_url_name'] ) ? $_POST['resume_url_name'] : array();
		$resume_urls      = isset( $_POST['resume_url'] ) ? $_POST['resume_url'] : array();

		foreach ( $resume_url_names as $index => $name ) {
			if ( ! empty( $name ) && ! empty( $resume_urls[ $index ] ) ) {
				$links[] = array(
					'name' => sanitize_text_field( stripslashes( $name ) ),
					'url'  => sanitize_text_field( stripslashes( $resume_urls[ $index ] ) )
				);
			}
		}

		update_post_meta( $post_id, '_links', $links );
	}
}

new WP_Resume_Manager_Writepanels();