<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = array(
	'job_manager_linkedin_api_key',
	'job_manager_linkedin_secret_key',
	'job_manager_apply_with_linkedin_cover_letter'
);

foreach ( $options as $option ) {
	delete_option( $option );
}