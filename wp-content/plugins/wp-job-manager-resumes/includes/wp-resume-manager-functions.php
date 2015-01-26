<?php
if ( ! function_exists( 'get_resumes' ) ) :
/**
 * Queries job listings with certain criteria and returns them
 *
 * @access public
 * @return void
 */
function get_resumes( $args = array() ) {
	global $wpdb;

	$args = wp_parse_args( $args, array(
		'search_location'   => '',
		'search_keywords'   => '',
		'search_categories' => array(),
		'offset'            => '',
		'posts_per_page'    => '-1',
		'orderby'           => 'date',
		'order'             => 'DESC',
		'featured'          => null
	) );

	$query_args = array(
		'post_type'           => 'resume',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => 1,
		'offset'              => absint( $args['offset'] ),
		'posts_per_page'      => intval( $args['posts_per_page'] ),
		'orderby'             => $args['orderby'],
		'order'               => $args['order'],
		'tax_query'           => array(),
		'meta_query'          => array()
	);

	if ( ! is_null( $args['featured'] ) ) {
		$query_args['meta_query'][] = array(
			'key'     => '_featured',
			'value'   => '1',
			'compare' => $args['featured'] ? '=' : '!='
		);
	}

	if ( ! empty( $args['search_categories'] ) ) {
		$field = is_numeric( $args['search_categories'][0] ) ? 'term_id' : 'slug';

		$query_args['tax_query'][] = array(
			'taxonomy' => 'resume_category',
			'field'    => $field,
			'terms'    => $args['search_categories'],
			'operator' => get_option( 'resume_manager_category_filter_type', 'all' ) == 'all' ? 'AND' : 'IN'
		);
	}

	// Location search - search geolocation data and location meta
	if ( $args['search_location'] ) {
		$location_post_ids = array_merge( $wpdb->get_col( $wpdb->prepare( "
		    SELECT DISTINCT post_id FROM {$wpdb->postmeta}
		    WHERE meta_key IN ( 'geolocation_city', 'geolocation_country_long', 'geolocation_country_short', 'geolocation_formatted_address', 'geolocation_state_long', 'geolocation_state_short', 'geolocation_street', 'geolocation_zipcode', '_candidate_location' )
		    AND meta_value LIKE '%%%s%%'
		", $args['search_location'] ) ), array( 0 ) );
	} else {
		$location_post_ids = array();
	}

	// Keyword search - search meta as well as post content
	if ( $args['search_keywords'] ) {
		$search_keywords              = array_map( 'trim', explode( ',', $args['search_keywords'] ) );
		$posts_search_keywords_sql    = array();
		$postmeta_search_keywords_sql = array();

		foreach ( $search_keywords as $keyword ) {
			$postmeta_search_keywords_sql[] = " meta_value LIKE '%" . esc_sql( $keyword ) . "%' ";
			$posts_search_keywords_sql[]    = "
				post_title LIKE '%" . esc_sql( $keyword ) . "%'
				OR post_content LIKE '%" . esc_sql( $keyword ) . "%'
			";
		}

		$keyword_post_ids = $wpdb->get_col( "
		    SELECT DISTINCT post_id FROM {$wpdb->postmeta}
		    WHERE " . implode( ' OR ', $postmeta_search_keywords_sql ) . "
		" );

		$keyword_post_ids = array_merge( $keyword_post_ids, $wpdb->get_col( "
		    SELECT ID FROM {$wpdb->posts}
		    WHERE ( " . implode( ' OR ', $posts_search_keywords_sql ) . " )
		    AND post_type = 'resume'
		    AND post_status = 'publish'
		" ), array( 0 ) );
	} else {
		$keyword_post_ids = array();
	}

	// Merge post ids
	if ( ! empty( $location_post_ids ) && ! empty( $keyword_post_ids ) ) {
		$query_args['post__in'] = array_intersect( $location_post_ids, $keyword_post_ids );
	} elseif ( ! empty( $location_post_ids ) || ! empty( $keyword_post_ids ) ) {
		$query_args['post__in'] = array_merge( $location_post_ids, $keyword_post_ids );
	}

	$query_args = apply_filters( 'resume_manager_get_resumes', $query_args );

	if ( empty( $query_args['meta_query'] ) ) {
		unset( $query_args['meta_query'] );
	}

	if ( empty( $query_args['tax_query'] ) ) {
		unset( $query_args['tax_query'] );
	}

	if ( $args['orderby'] == 'featured' ) {
		$query_args['orderby']  = 'meta_key';
		$query_args['meta_key'] = '_featured';
		add_filter( 'posts_clauses', 'order_featured_resume' );
	}

	// Filter args
	$query_args = apply_filters( 'get_resumes_query_args', $query_args );

	do_action( 'before_get_resumes', $query_args );

	$result = new WP_Query( $query_args );

	do_action( 'after_get_resumes', $query_args );

	remove_filter( 'posts_clauses', 'order_featured_resume' );

	return $result;
}
endif;

if ( ! function_exists( 'order_featured_resume' ) ) :
	/**
	 * WP Core doens't let us change the sort direction for invidual orderby params - http://core.trac.wordpress.org/ticket/17065
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	function order_featured_resume( $args ) {
		global $wpdb;

		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_title ASC";

		return $args;
	}
endif;

if ( ! function_exists( 'get_resume_share_link' ) ) :
/**
 * Generates a sharing link which allows someone to view the resume directly (even if permissions do not usually allow it)
 *
 * @access public
 * @return array
 */
function get_resume_share_link( $resume_id ) {
	if ( ! $key = get_post_meta( $resume_id, 'share_link_key', true ) ) {
		$key = wp_generate_password( 32, false );
		update_post_meta( $resume_id, 'share_link_key', $key );
	}

	return add_query_arg( 'key', $key, get_permalink( $resume_id ) );
}
endif;

if ( ! function_exists( 'get_resume_categories' ) ) :
/**
 * Outputs a form to submit a new job to the site from the frontend.
 *
 * @access public
 * @return array
 */
function get_resume_categories() {
	if ( ! get_option( 'resume_manager_enable_categories' ) ) {
		return array();
	}

	return get_terms( "resume_category", array(
		'orderby'       => 'name',
	    'order'         => 'ASC',
	    'hide_empty'    => false,
	) );
}
endif;

if ( ! function_exists( 'resume_manager_get_filtered_links' ) ) :
/**
 * Shows links after filtering resumes
 */
function resume_manager_get_filtered_links( $args = array() ) {

	$links = apply_filters( 'resume_manager_resume_filters_showing_resumes_links', array(
		'reset' => array(
			'name' => __( 'Reset', 'wp-job-manager-resumes' ),
			'url'  => '#'
		)
	), $args );

	$return = '';

	foreach ( $links as $key => $link ) {
		$return .= '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $key ) . '">' . $link['name'] . '</a>';
	}

	return $return;
}
endif;

/**
 * True if an the user can edit a resume.
 *
 * @return bool
 */
function resume_manager_user_can_edit_resume( $resume_id ) {
	$can_edit = true;
	$resume   = get_post( $resume_id );

	if ( ! is_user_logged_in() ) {
		$can_edit = false;
	} elseif ( $resume->post_author != get_current_user_id() ) {
		$can_edit = false;
	}

	return apply_filters( 'resume_manager_user_can_edit_resume', $can_edit, $resume_id );
}

/**
 * True if an the user can browse resumes.
 *
 * @return bool
 */
function resume_manager_user_can_browse_resumes() {
	$can_browse = true;

	$cap = strtolower( get_option( 'resume_manager_browse_resume_capability' ) );

	if ( $cap && ! current_user_can( $cap ) ) {
		$can_browse = false;
	}

	return apply_filters( 'resume_manager_user_can_browse_resumes', $can_browse );
}

/**
 * True if an the user can view a resume.
 *
 * @return bool
 */
function resume_manager_user_can_view_resume( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );

	// Allow previews
	if ( $resume->post_status === 'preview' ) {
		return true;
	}

	if ( $resume->post_status === 'expired' ) {
		$can_view = false;
	}

	$cap = strtolower( get_option( 'resume_manager_view_resume_capability' ) );

	if ( $cap && ! current_user_can( $cap ) ) {
		$can_view = false;
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_resume', $can_view, $resume_id );
}

/**
 * True if an the user can view a resume.
 *
 * @return bool
 */
function resume_manager_user_can_view_contact_details( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );

	$cap = strtolower( get_option( 'resume_manager_contact_resume_capability' ) );

	if ( $cap && ! current_user_can( $cap ) )
		$can_view = false;

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_contact_details', $can_view, $resume_id );
}

if ( ! function_exists( 'get_resume_post_statuses' ) ) :
/**
 * Get post statuses used for resumes
 *
 * @access public
 * @return array
 */
function get_resume_post_statuses() {
	return apply_filters( 'resume_post_statuses', array(
		'draft'           => _x( 'Draft', 'post status', 'wp-job-manager-resumes' ),
		'expired'         => _x( 'Expired', 'post status', 'wp-job-manager-resumes' ),
		'hidden'          => _x( 'Hidden', 'post status', 'wp-job-manager-resumes' ),
		'preview'         => _x( 'Preview', 'post status', 'wp-job-manager-resumes' ),
		'pending'         => _x( 'Pending approval', 'post status', 'wp-job-manager-resumes' ),
		'pending_payment' => _x( 'Pending payment', 'post status', 'wp-job-manager-resumes' ),
		'publish'         => _x( 'Published', 'post status', 'wp-job-manager-resumes' ),
	) );
}
endif;