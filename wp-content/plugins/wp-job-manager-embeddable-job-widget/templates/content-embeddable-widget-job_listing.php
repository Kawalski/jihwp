<li class="embeddable-job-widget-listing">
	<a href="<?php the_job_permalink(); ?>" target="_blank">
		<div class="embeddable-job-widget-listing-title">
			<?php the_title(); ?>
		</div>
		<div class="embeddable-job-widget-listing-meta">
			<?php
				$meta = array();

				if ( $data = get_the_job_type() ) {
					$meta[] = '<span class="embeddable-job-widget-listing-job-type">' . $data->name . '</span>';
				}
				if ( $data = get_the_job_location() ) {
					$meta[] = '<span class="embeddable-job-widget-listing-job-location">' . $data . '</span>';
				}
				if ( $data = get_the_company_name() ) {
					$meta[] = '<span class="embeddable-job-widget-listing-job-company">' . $data . '</span>';
				}

				echo implode( ' - ', $meta );
			?>
		</div>
	</a>
</li>