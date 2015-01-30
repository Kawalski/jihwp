jQuery(document).ready(function($) {
	jQuery('.bookmark-notice').click(function() {
		jQuery('.bookmark-details').slideToggle();
		jQuery(this).toggleClass('open');
		return false;
	});
});