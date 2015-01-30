<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WP_Job_Manager_Job_Tags_Shortcodes {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'jobs_by_tag', array( $this, 'jobs_by_tag' ) );
		add_shortcode( 'job_tag_cloud', array( $this, 'job_tag_cloud' ) );

		// Change core output jobs shortcode
		add_action( 'job_manager_job_filters_search_jobs_end', array( $this, 'show_tag_filter' ) );
		add_filter( 'job_manager_get_listings', array( $this, 'apply_tag_filter' ) );
		add_filter( 'job_manager_output_jobs_defaults', array( $this, 'output_jobs_defaults' ) );
	}

	/**
	 * Change default args
	 */
	public function output_jobs_defaults( $atts ) {
		$atts['show_tags'] = true;
		return $atts;
	}

	/**
	 * Show the tag cloud
	 */
	public function show_tag_filter( $shortcode_atts ) {

		if ( isset( $shortcode_atts['show_tags'] ) && ( $shortcode_atts['show_tags'] === false || (string) $shortcode_atts['show_tags'] == 'false' ) )
			return;

		if ( wp_count_terms( 'job_listing_tag' ) == 0 )
			return;

		wp_enqueue_script( 'wp-job-manager-ajax-tag-filters', JOB_MANAGER_TAGS_PLUGIN_URL . '/assets/js/tag-filter.js', array( 'jquery' ), '1.0', true );

		$atts = array(
			'smallest'                  => 1,
		    'largest'                   => 2,
		    'unit'                      => 'em',
		    'number'                    => 25,
		    'format'                    => 'flat',
		    'separator'                 => "\n",
		    'orderby'                   => 'count',
		    'order'                     => 'DESC',
		    'exclude'                   => null,
		    'include'                   => null,
		    'link'                      => 'view',
		    'taxonomy'                  => 'job_listing_tag',
		    'echo'                      => false,
		    'topic_count_text_callback' => array( $this, 'tag_cloud_text_callback' )
		);

		$html = '<div class="filter_wide filter_by_tag">' . __( 'Filter by tag:', 'wp-job-manager-tags' ) . ' ' . wp_tag_cloud( apply_filters( 'job_filter_tag_cloud', $atts ) ) . '</div>';

		$html = preg_replace( "/<a(.*)href='([^'']*)'(.*)>/", '<a href="#"$1$3>', $html );

		echo $html;
	}

	/**
	 * Filter by tag
	 */
	public function apply_tag_filter( $args ) {
		$params = array();
		if ( isset( $_POST['form_data'] ) ) {

			parse_str( $_POST['form_data'], $params );

			if ( isset( $params['job_tag'] ) ) {
				$tags      = array_filter( $params['job_tag'] );
				$tag_array = array();

				foreach ( $tags as $tag ) {
					$tag = get_term_by( 'name', $tag, 'job_listing_tag' );
					$tag_array[] = $tag->slug;
				}

				$args['tax_query'][] = array(
					'taxonomy' => 'job_listing_tag',
					'field'    => 'slug',
					'terms'    => $tag_array,
					'operator' => "IN"
				);

				add_filter( 'job_manager_get_listings_custom_filter', '__return_true' );
				add_filter( 'job_manager_get_listings_custom_filter_text', array( $this, 'apply_tag_filter_text' ) );
				add_filter( 'job_manager_get_listings_custom_filter_rss_args', array( $this, 'apply_tag_filter_rss' ) );
			}

		}

		return $args;
	}

	/**
	 * Append 'showing' text
	 * @return string
	 */
	public function apply_tag_filter_text( $text ) {
		$params = array();
		parse_str( $_POST['form_data'], $params );

		$text .= ' ' . __( 'tagged', 'wp-job-manager-tags' ) . ' ' . implode( ', ', array_filter( $params['job_tag'] ) );

		return $text;
	}

	/**
	 * apply_tag_filter_rss
	 * @return array
	 */
	public function apply_tag_filter_rss( $args ) {
		$params = array();
		parse_str( $_POST['form_data'], $params );

		$args['job_tags'] = implode( ',', array_filter( $params['job_tag'] ) );

		return $args;
	}

	/**
	 * Jobs by tag shortcode
	 *
	 * @return string
	 */
	public function jobs_by_tag( $atts ) {
		global $job_manager;

		ob_start();

		extract( shortcode_atts( array(
			'per_page'        => '-1',
			'orderby'         => 'date',
			'order'           => 'desc',
			'tag'             => '',
			'tags'            => ''
		), $atts ) );

		$tags   = array_filter( array_map( 'sanitize_title', explode( ',', $tags ) ) );

		if ( $tag )
			$tags[] = sanitize_title( $tag );

		if ( ! $tags )
			return;

		$args = array(
			'post_type'           => 'job_listing',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $per_page,
			'orderby'             => $orderby,
			'order'               => $order,
		);

		$args['tax_query'] = array(
			array(
				'taxonomy' => 'job_listing_tag',
				'field'    => 'slug',
				'terms'    => $tags
			)
		);

		if ( get_option( 'job_manager_hide_filled_positions' ) == 1 )
			$args['meta_query'] = array(
				array(
					'key'     => '_filled',
					'value'   => '1',
					'compare' => '!='
				)
			);

		$jobs = new WP_Query( apply_filters( 'job_manager_output_jobs_args', $args ) );

		if ( $jobs->have_posts() ) : ?>

			<ul class="job_listings">

				<?php while ( $jobs->have_posts() ) : $jobs->the_post(); ?>

					<?php get_job_manager_template_part( 'content', 'job_listing' ); ?>

				<?php endwhile; ?>

			</ul>

		<?php else :

			echo '<p>' . sprintf( __( 'No jobs found tagged with %s.', 'wp-job-manager-tags' ), implode( ', ', $tags ) ) . '</p>';

		endif;

		wp_reset_postdata();

		return '<div class="job_listings">' . ob_get_clean() . '</div>';
	}

	/**
	 * Job Tag cloud shortcode
	 */
	public function job_tag_cloud( $atts ) {
		ob_start();

		$atts = shortcode_atts( array(
			'smallest'                  => 8,
		    'largest'                   => 22,
		    'unit'                      => 'pt',
		    'number'                    => 45,
		    'format'                    => 'flat',
		    'separator'                 => "\n",
		    'orderby'                   => 'count',
		    'order'                     => 'DESC',
		    'exclude'                   => null,
		    'include'                   => null,
		    'link'                      => 'view',
		    'taxonomy'                  => 'job_listing_tag',
		    'echo'                      => false,
		    'topic_count_text_callback' => array( $this, 'tag_cloud_text_callback' )
		), $atts );

		$html = wp_tag_cloud( apply_filters( 'job_tag_cloud', $atts ) );

		if ( ! apply_filters( 'enable_job_tag_archives', get_option( 'job_manager_enable_tag_archive' ) ) )
			$html = str_replace( '</a>', '</span>', preg_replace( "/<a(.*)href='([^'']*)'(.*)>/", '<span$1$3>', $html ) );

		return $html;
	}

	/**
	 * tag_cloud_text_callback
	 */
	public function tag_cloud_text_callback( $count ) {
		return sprintf( _n( '%s job', '%s jobs', $count, 'wp-job-manager-tags' ), number_format_i18n( $count ) );
	}
}

new WP_Job_Manager_Job_Tags_Shortcodes();