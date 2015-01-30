jQuery('.filter_by_tag').on('click', 'a', function() {
	var tag = jQuery(this).text();
	var existing_tag = jQuery('.filter_by_tag').find('input[value="' + tag + '"]');

	if ( existing_tag.size() > 0 ) {
		jQuery(existing_tag).remove();
		jQuery(this).removeClass('active');
	} else {
		jQuery('.filter_by_tag').append('<input type="hidden" name="job_tag[]" value="' + tag + '" />');
		jQuery(this).addClass('active');
	}

	var target = jQuery(this).closest( 'div.job_listings' );

	target.trigger( 'update_results', [ 1, false ] );

	return false;
});

jQuery( '.job_listings' ).on( 'reset', function() {
	jQuery('.filter_by_tag a.active', this).removeClass('active');
	jQuery('.filter_by_tag input', this).remove();
});