<form method="post" class="apply-with-xing-details wp-job-manager-application-details" style="display:none">
	<div class="apply-with-xing-profile">
		<img src="" />
		<h2 class="profile-name"></h2>
		<strong class="profile-headline"></strong>
		<em class="profile-location"></em>
		<dl>
			<dt class="profile-current-positions"><?php _e( 'Current', 'wp-job-manager-apply-with-xing' ); ?></dt>
			<dd class="profile-current-positions"><ul></ul></dd>

			<dt class="profile-past-positions"><?php _e( 'Past', 'wp-job-manager-apply-with-xing' ); ?></dt>
			<dd class="profile-past-positions"><ul></ul></dd>

			<dt class="profile-educations"><?php _e( 'Education', 'wp-job-manager-apply-with-xing' ); ?></dt>
			<dd class="profile-educations"><ul></ul></dd>

			<dt class="profile-email"><?php _e( 'Email', 'wp-job-manager-apply-with-xing' ); ?></dt>
			<dd class="profile-email"></dd>

			<?php if ( in_array( $cover_letter, array( 'optional', 'required' ) ) ) : ?>
				<dt class="apply-with-xing-cover-letter"><label for="apply-with-xing-cover-letter"><?php _e( 'Cover letter', 'wp-job-manager-apply-with-xing' ); ?> <?php if ( 'optional' === $cover_letter ) _e( '(optional)', 'wp-job-manager-apply-with-xing' ); ?></label></dt>
				<dd class="apply-with-xing-cover-letter">
					<textarea name="apply-with-xing-cover-letter" id="apply-with-xing-cover-letter" <?php if ( 'required' === $cover_letter ) echo 'required="required"'; ?>><?php echo _x( 'To whom it may concern,', 'default cover letter', 'wp-job-manager-apply-with-xing' ); ?>


<?php printf( _x( 'I am very interested in the %s position at %s. I believe my skills and work experience make me an ideal candidate for this role. I look forward to speaking with you soon about this position. Thank you for your consideration.', 'default cover letter', 'wp-job-manager-apply-with-xing' ), $job_title, $company_name ); ?>


<?php echo _x( 'Best regards,', 'default cover letter', 'wp-job-manager-apply-with-xing' ); ?> </textarea>
				</dd>

			<?php endif; ?>
		</dl>
		<p class="apply-with-xing-submit">
			<input type="submit" name="apply-with-xing-submit" value="<?php _e( 'Submit Application', 'wp-job-manager-apply-with-xing' ); ?>" /> <?php printf( __( 'Clicking submit will submit your full profile to %s.', 'wp-job-manager-apply-with-xing' ), '<strong>' . esc_html( $company_name ) . '</strong>' ); ?>
			<input type="hidden" name="apply-with-xing-profile-data" id="apply-with-xing-profile-data" />
			<input type="hidden" name="apply-with-xing-job-id" value="<?php echo esc_attr( $job_id ); ?>" />
		</p>
	</div>
</form>