<tr>
	<td>
		<input type="text" name="resume_experience_employer[]" value="<?php echo esc_attr( $employer ); ?>" />
	</td>
	<td>
		<input type="text" name="resume_experience_job_title[]" value="<?php echo esc_attr( $job_title ); ?>" />
	</td>
	<td>
		<input type="text" name="resume_experience_date[]" value="<?php echo esc_attr( $date ); ?>" />
	</td>
	<td>
		<textarea name="resume_experience_notes[]" rows="4" cols="20" ><?php echo esc_textarea( $notes ); ?></textarea>
	</td>
</tr>