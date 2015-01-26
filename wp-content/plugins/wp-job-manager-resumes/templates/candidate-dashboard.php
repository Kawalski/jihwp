<?php $column_count = get_option( 'resume_manager_enable_categories' ) ? 5 : 4; ?>
<div id="resume-manager-candidate-dashboard">
	<p><?php _e( 'Your resume(s) are shown in the table below.', 'wp-job-manager-resumes' ); ?></p>
	<table class="resume-manager-resumes">
		<thead>
			<tr>
				<th class="resume_title"><?php _e( 'Name', 'wp-job-manager-resumes' ); ?></th>
				<th class="candidate-title"><?php _e( 'Title', 'wp-job-manager-resumes' ); ?></th>
				<th class="candidate-location"><?php _e( 'Location', 'wp-job-manager-resumes' ); ?></th>
				<?php if ( get_option( 'resume_manager_enable_categories' ) ) : ?>
					<th class="resume-category"><?php _e( 'Category', 'wp-job-manager-resumes' ); ?></th>
				<?php endif; ?>
				<th class="status"><?php _e( 'Status', 'wp-job-manager-resumes' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $resumes ) : ?>
				<tr>
					<td colspan="<?php echo $column_count; ?>"><?php _e( 'You do not have any active resume listings.', 'wp-job-manager-resumes' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $resumes as $resume ) : ?>
					<tr>
						<td class="resume_title">
							<a href="<?php echo get_permalink( $resume->ID ); ?>"><?php echo $resume->post_title; ?></a>
							<ul class="candidate-dashboard-actions">
								<?php
									$actions = array();

									switch ( $resume->post_status ) {
										case 'publish' :
											$actions['edit'] = array( 'label' => __( 'Edit', 'wp-job-manager-resumes' ), 'nonce' => false );
											$actions['hide'] = array( 'label' => __( 'Hide', 'wp-job-manager-resumes' ), 'nonce' => true );
										break;
										case 'hidden' :
											$actions['edit'] = array( 'label' => __( 'Edit', 'wp-job-manager-resumes' ), 'nonce' => false );
											$actions['publish'] = array( 'label' => __( 'Publish', 'wp-job-manager-resumes' ), 'nonce' => true );
										break;
										case 'expired' :
											if ( get_option( 'resume_manager_submit_page_id' ) ) {
												$actions['relist'] = array( 'label' => __( 'Relist', 'wp-job-manager-resumes' ), 'nonce' => true );
											}
										break;
									}

									$actions['delete'] = array( 'label' => __( 'Delete', 'wp-job-manager-resumes' ), 'nonce' => true );

									$actions = apply_filters( 'resume_manager_my_resume_actions', $actions, $resume );

									foreach ( $actions as $action => $value ) {
										$action_url = add_query_arg( array( 'action' => $action, 'resume_id' => $resume->ID ) );
										if ( $value['nonce'] )
											$action_url = wp_nonce_url( $action_url, 'resume_manager_my_resume_actions' );
										echo '<li><a href="' . $action_url . '" class="candidate-dashboard-action-' . $action . '">' . $value['label'] . '</a></li>';
									}
								?>
							</ul>
						</td>
						<td class="candidate-title"><?php the_candidate_title( '', '', true, $resume ); ?></td>
						<td class="candidate-location"><?php the_candidate_location( false, $resume ); ?></td>
						<?php if ( get_option( 'resume_manager_enable_categories' ) ) : ?>
							<td class="resume-category"><?php the_resume_category( $resume ); ?></td>
						<?php endif; ?>
						<td class="status">
							<?php the_resume_status( $resume ); ?>
							<small><?php
								if ( ! empty( $resume->_resume_expires ) && strtotime( $resume->_resume_expires ) > current_time( 'timestamp' ) ) {
									printf( __( 'Expires %s', 'wp-job-manager-resumes' ), date_i18n( get_option( 'date_format' ), strtotime( $resume->_resume_expires ) ) );
								} else {
									echo date_i18n( get_option( 'date_format' ), strtotime( $resume->post_date ) );
								}
							?></small>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<?php if ( $page_id = get_option( 'resume_manager_submit_page_id' ) ) : ?>
			<tfoot>
				<tr>
					<td colspan="<?php echo $column_count; ?>">
						<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php _e( 'Add Resume', 'wp-job-manager-resumes' ); ?></a>
					</td>
				</tr>
			</tfoot>
		<?php endif; ?>
	</table>
	<?php get_job_manager_template( 'pagination.php', array( 'max_num_pages' => $max_num_pages ) ); ?>
</div>