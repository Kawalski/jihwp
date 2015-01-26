<?php if ( is_user_logged_in() ) : ?>

	<fieldset>
		<label><?php _e( 'Your account', 'wp-job-manager-resumes' ); ?></label>
		<div class="field account-sign-in">
			<?php
				$user = wp_get_current_user();
				printf( __( 'You are currently signed in as <strong>%s</strong>.', 'wp-job-manager-resumes' ), $user->user_login );
			?>

			<a class="button" href="<?php echo apply_filters( 'submit_resume_form_logout_url', wp_logout_url( get_permalink() ) ); ?>"><?php _e( 'Sign out', 'wp-job-manager-resumes' ); ?></a>
		</div>
	</fieldset>

<?php else :

	$account_required     = resume_manager_user_requires_account();
	$registration_enabled = resume_manager_enable_registration();
	?>
	<fieldset>
		<label><?php _e( 'Have an account?', 'wp-job-manager-resumes' ); ?></label>
		<div class="field account-sign-in">
			<a class="button" href="<?php echo apply_filters( 'submit_resume_form_login_url', wp_login_url( get_permalink() ) ); ?>"><?php _e( 'Sign in', 'wp-job-manager-resumes' ); ?></a>

			<?php if ( $registration_enabled ) : ?>

				<?php _e( 'If you don&rsquo;t have an account you can create one below by entering your email address. A password will be automatically emailed to you.', 'wp-job-manager-resumes' ); ?>

			<?php elseif ( $account_required ) : ?>

				<?php echo apply_filters( 'submit_resume_form_login_required_message',  __( 'You must sign in to submit a resume.', 'wp-job-manager-resumes' ) ); ?>

			<?php endif; ?>
		</div>
	</fieldset>

<?php endif; ?>