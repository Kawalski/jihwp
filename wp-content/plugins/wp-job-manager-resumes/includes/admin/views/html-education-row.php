<tr>
	<td>
		<input type="text" name="resume_education_location[]" value="<?php echo esc_attr( $location ); ?>" />
	</td>
	<td>
		<input type="text" name="resume_education_qualification[]" value="<?php echo esc_attr( $qualification ); ?>" />
	</td>
	<td>
		<input type="text" name="resume_education_date[]" value="<?php echo esc_attr( $date ); ?>" />
	</td>
	<td>
		<textarea name="resume_education_notes[]" rows="4" cols="20" ><?php echo esc_textarea( $notes ); ?></textarea>
	</td>
</tr>