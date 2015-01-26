<?php

include_once( 'class-wp-resume-manager-form-submit-resume.php' );

/**
 * WP_Resume_Manager_Form_Edit_Resume class.
 */
class WP_Resume_Manager_Form_Edit_Resume extends WP_Resume_Manager_Form_Submit_Resume {

	public static $form_name = 'edit-resume';

	/**
	 * Constructor
	 */
	public static function init() {
		self::$resume_id = ! empty( $_REQUEST['resume_id'] ) ? absint( $_REQUEST[ 'resume_id' ] ) : 0;

		if  ( ! resume_manager_user_can_edit_resume( self::$resume_id ) ) {
			self::$resume_id = 0;
		}
	}

	/**
	 * output function.
	 *
	 * @access public
	 * @return void
	 */
	public static function output() {
		self::submit_handler();
		self::submit();
	}

	/**
	 * Submit Step
	 */
	public static function submit() {
		global $post;

		$resume = get_post( self::$resume_id );

		if ( empty( self::$resume_id  ) || ( $resume->post_status !== 'publish' && $resume->post_status !== 'hidden' ) ) {
			echo wpautop( __( 'Invalid resume', 'wp-job-manager-resumes' ) );
			return;
		}

		self::init_fields();

		foreach ( self::$fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( ! isset( self::$fields[ $group_key ][ $key ]['value'] ) ) {
					if ( 'candidate_name' === $key ) {
						self::$fields[ $group_key ][ $key ]['value'] = $resume->post_title;

					} elseif ( 'resume_content' === $key ) {
						self::$fields[ $group_key ][ $key ]['value'] = $resume->post_content;

					} elseif ( ! empty( $field['taxonomy'] ) ) {
						self::$fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $resume->ID, $field['taxonomy'], array( 'fields' => 'ids' ) );

					} elseif ( 'resume_skills' === $key ) {
						self::$fields[ $group_key ][ $key ]['value'] = implode( ', ', wp_get_object_terms( $resume->ID, 'resume_skill', array( 'fields' => 'names' ) ) );

					} else {
						self::$fields[ $group_key ][ $key ]['value'] = get_post_meta( $resume->ID, '_' . $key, true );
					}
				}
			}
		}

		self::$fields = apply_filters( 'submit_resume_form_fields_get_resume_data', self::$fields, $resume );

		get_job_manager_template( 'resume-submit.php', array(
			'class'              => __CLASS__,
			'form'               => self::$form_name,
			'job_id'             => '',
			'resume_id'          => self::get_resume_id(),
			'action'             => self::get_action(),
			'resume_fields'      => self::get_fields( 'resume_fields' ),
			'step'               => self::get_step(),
			'submit_button_text' => __( 'Save changes', 'wp-job-manager-resumes' )
		), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Submit Step is posted
	 */
	public static function submit_handler() {
		if ( empty( $_POST['submit_resume'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) )
			return;

		try {

			// Init fields
			self::init_fields();

			// Get posted values
			$values = self::get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = self::validate_fields( $values ) ) ) )
				throw new Exception( $return->get_error_message() );

			// Update the resume
			self::save_resume( $values['resume_fields']['candidate_name'], $values['resume_fields']['resume_content'], 'publish', $values );
			self::update_resume_data( $values );

			// Successful
			echo '<div class="job-manager-message">' . __( 'Your changes have been saved.', 'wp-job-manager-resumes' ), ' <a href="' . get_permalink( self::$resume_id ) . '">' . __( 'View Resume &rarr;', 'wp-job-manager-resumes' ) . '</a>' . '</div>';

		} catch ( Exception $e ) {
			echo '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			return;
		}
	}
}