<?php global $indeed_job; ?>
<li id="job_listing-<?php echo md5( $indeed_job->jobtitle ); ?>" class="indeed_job_listing job_listing type-job_listing" data-longitude="<?php echo esc_attr( $indeed_job->longitude ); ?>" data-latitude="<?php echo esc_attr( $indeed_job->latitude ); ?>" data-title="<?php echo $indeed_job->jobtitle; ?>" data-href="<?php echo $indeed_job->url; ?>">
	<a href="<?php echo $indeed_job->url; ?>" target="_blank" onmousedown="<?php echo $indeed_job->onmousedown; ?>">
		<div class="logo col-sm-2 col-md-1 col-lg-1">
			<?php echo '<img class="company_logo" src="' . JOB_MANAGER_PLUGIN_URL . '/assets/images/company.png' . '" alt="Logo" />'; ?>
		</div>

		<div class="position col-xs-12 col-sm-10 col-md-6 col-lg-5">
			<h3><?php echo $indeed_job->jobtitle; ?></h3>
			<div class="company">
				<strong><?php echo $indeed_job->company; ?></strong>
				<span class="tagline"><?php printf( __( 'Source: %s', 'jobify' ), $indeed_job->source ); ?></span>
			</div>
		</div>

		<div class="location col-xs-12 col-md-5 col-lg-4">
			<?php echo $indeed_job->formattedLocation; ?>
		</div>

		<ul class="meta col-lg-2">
			<li class="job-type <?php echo $indeed_job->job_type; ?>"><?php echo $indeed_job->job_type_name; ?></li>
			<li class="date"><date><?php printf( __( 'Posted %s ago', 'jobify' ), human_time_diff( strtotime( $indeed_job->date ), current_time( 'timestamp' ) ) ); ?></date></li>
		</ul>
	</a>
</li>
