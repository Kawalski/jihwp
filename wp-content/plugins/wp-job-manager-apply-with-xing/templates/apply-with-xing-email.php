<?php if ( $cover_letter ) : ?>
	<?php echo wpautop( wptexturize( $cover_letter ) ); ?>
<?php else : ?>
	<p>
	<?php printf( __( 'Dear %s', 'wp-job-manager-apply-with-xing' ), $company_name ); ?>,<br/><br/>
	<?php printf( __( '%s has applied for the job of %s. See below for %s\'s profile snapshot.', 'wp-job-manager-apply-with-xing' ), $profile_data->display_name, $job_title, $profile_data->first_name ); ?>
	</p>
<?php endif; ?>

<table style="border:1px solid #ccc; background: #eee; padding: 14px; text-align:left;" cellpadding="6" cellspacing="0">
	<tr>
		<td rowspan="3"><img src="<?php echo esc_attr( $profile_data->photo_urls->size_96x96 ); ?>" /></td>
		<td><strong style="font-size: 16px;"><?php echo esc_html( $profile_data->display_name ); ?></strong></td>
	</tr>
	<tr>
		<td><strong><?php echo esc_html( $profile_data->haves ); ?></strong></td>
	</tr>
	<tr>
		<td><em><?php
			$location = __( 'Unknown location', 'wp-job-manager-apply-with-xing' );
			$address  = false;

			if ( $profile_data->business_address ) {
				$address = $profile_data->business_address;
			} elseif ( $profile_data->private_address ) {
				$address = $profile_data->private_address;
			}

			if ( $address ) {
				$location = '';
				if ( $address->city ) {
					$location = $address->city . ', ';
				}
				$location .= $address->country;
			}

			echo esc_html( $location );
		?></em></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Current', 'wp-job-manager-apply-with-xing' ); ?></th>
		<td valign="top"><?php
			if ( $profile_data->professional_experience && $profile_data->professional_experience->primary_company ) {
				$company = $profile_data->professional_experience->primary_company;
				echo '<strong>' . $company->title . '</strong> - ' . $company->name . '<br/>';
				if ( $company->description ) {
					echo $company->description . '<br/><br/>';
				} else {
					echo '<br/>';
				}
			}
		?></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Past', 'wp-job-manager-apply-with-xing' ); ?></th>
		<td valign="top"><?php
			if ( $profile_data->professional_experience && $profile_data->professional_experience->non_primary_companies ) {
				foreach ( $profile_data->professional_experience->non_primary_companies as $company ) {
					echo '<strong>' . $company->title . '</strong> - ' . $company->name . '<br/>';
					if ( $company->description ) {
						echo $company->description . '<br/><br/>';
					} else {
						echo '<br/>';
					}
				}
			}
		?></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Education', 'wp-job-manager-apply-with-xing' ); ?></th>
		<td valign="top"><?php
			if ( $profile_data->educational_background && $profile_data->educational_background->schools ) {
				foreach ( $profile_data->educational_background->schools as $school ) {
					echo '<strong>' . $school->name . '</strong>';
					if ( ! empty( $school->end_date ) ) {
						echo ' (' . $school->end_date . ')';
					}
					if ( ! empty( $school->subject ) ) {
						echo '<br/>' . $school->subject;
					}
					if ( ! empty( $school->degree ) ) {
						echo ' - ' . $school->degree;
					}
					echo '<br/><br/>';
				}
			}
		?></td>
	</tr>
	<tr>
		<th valign="top"><?php _e( 'Email address', 'wp-job-manager-apply-with-xing' ); ?></th>
		<td valign="top"><?php echo make_clickable( sanitize_text_field( $profile_data->active_email ) ); ?></td>
	</tr>
</table>

<p><a href="<?php echo esc_url( $profile_data->permalink ); ?>"><?php _e( 'View complete profile', 'wp-job-manager-apply-with-xing' ); ?></a></p>