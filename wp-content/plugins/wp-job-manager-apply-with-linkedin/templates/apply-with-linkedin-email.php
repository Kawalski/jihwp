<p>
	<?php printf( __( 'Dear %s', 'wp-job-manager-apply-with-linkedin' ), $company_name ); ?>,<br/><br/>
	<?php printf( __( '%s has applied for the job of %s. See below for %s\'s profile snapshot.', 'wp-job-manager-apply-with-linkedin' ), $profile_data->formattedName, $job_title, $profile_data->firstName ); ?>
</p>

<table style="border:1px solid #ccc; background: #eee; padding: 14px; text-align:left;" cellpadding="6" cellspacing="0">
	<tr>
		<td rowspan="3"><img src="<?php echo esc_attr( $profile_data->pictureUrl ); ?>" /></td>
		<td><strong style="font-size: 16px;"><?php echo esc_html( $profile_data->formattedName ); ?></strong></td>
	</tr>
	<tr>
		<td><strong><?php echo esc_html( $profile_data->headline ); ?></strong></td>
	</tr>
	<tr>
		<td><em><?php echo esc_html( $profile_data->location->name ); ?></em></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Current', 'wp-job-manager-apply-with-linkedin' ); ?></th>
		<td valign="top"><?php 
			if ( $profile_data->threeCurrentPositions->_total > 0 ) {
				foreach ( $profile_data->threeCurrentPositions->values as $position ) {
					echo $position->title;
					if ( ! empty( $position->company ) ) {
						echo ' - ' . $position->company->name;
					}
					echo '</br>';
				}
			}
		?></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Past', 'wp-job-manager-apply-with-linkedin' ); ?></th>
		<td valign="top"><?php 
			if ( $profile_data->threePastPositions->_total > 0 ) {
				foreach ( $profile_data->threePastPositions->values as $position ) {
					echo $position->title;
					if ( ! empty( $position->company ) ) {
						echo ' - ' . $position->company->name;
					}
					echo '</br>';
				}
			}
		?></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Education', 'wp-job-manager-apply-with-linkedin' ); ?></th>
		<td valign="top"><?php 
			if ( $profile_data->educations->_total > 0 ) {
				foreach ( $profile_data->educations->values as $education ) {
					echo $education->schoolName;
					if ( ! empty( $education->degree ) ) {
						echo ', ' . $education->degree;
					}
					echo '</br>';
				}
			}
		?></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Email address', 'wp-job-manager-apply-with-linkedin' ); ?></th>
		<td valign="top"><?php echo make_clickable( sanitize_text_field( $profile_data->emailAddress ) ); ?></td>
	</tr>
	<?php if ( $cover_letter ) : ?>
		<tr>
			<th valign="top"><?php _e( 'Cover letter', 'wp-job-manager-apply-with-linkedin' ); ?></th>
			<td valign="top"><?php echo wpautop( wptexturize( $cover_letter ) ); ?></td>
		</tr>
	<?php endif; ?>
</table>

<p><a href="<?php echo esc_url( $profile_data->publicProfileUrl ); ?>"><?php _e( 'View complete profile', 'wp-job-manager-apply-with-linkedin' ); ?></a></p>