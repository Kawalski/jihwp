<?php
switch ( $resume->post_status ) :
	case 'publish' :
		if ( resume_manager_user_can_view_resume( $resume->ID ) ) {
			printf( '<p>' . __( 'Resume submitted successfully. To view your resume <a href="%s">click here</a>.', 'wp-job-manager-resumes' ) . '</p>', get_permalink( $resume->ID ) );
		} else {
			print( '<p>' . __( 'Resume submitted successfully.', 'wp-job-manager-resumes' ) . '</p>' );
		}
	break;
	case 'pending' :
		printf( '<p>' . __( 'Resume submitted successfully. Your resume will be visible once approved.', 'wp-job-manager-resumes' ) . '</p>', get_permalink( $resume->ID ) );
	break;
	default :
		do_action( 'resume_manager_resume_submitted_content_' . str_replace( '-', '_', sanitize_title( $resume->post_status ) ), $resume );
	break;
endswitch;