<?php echo get_job_application_avatar( $application->ID ) ?>
<h3><?php echo $application->post_title; ?></h3>
<span class="job-application-rating"><span style="width: <?php echo ( get_job_application_rating( $application->ID ) / 5 ) * 100; ?>%;"></span></span>