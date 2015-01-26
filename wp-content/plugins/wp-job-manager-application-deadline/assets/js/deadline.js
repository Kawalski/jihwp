jQuery(document).ready(function($) {
	jQuery( "#job_deadline, #_application_deadline" ).datepicker( {
		minDate: 0,
		"dateFormat": wp_job_manager_deadline_args.date_format,
	    monthNames: wp_job_manager_deadline_args.monthNames,
	    monthNamesShort: wp_job_manager_deadline_args.monthNamesShort,
	    dayNames: wp_job_manager_deadline_args.dayNames,
	    dayNamesShort: wp_job_manager_deadline_args.dayNamesShort,
	    dayNamesMin: wp_job_manager_deadline_args.dayNamesMin
	} );
});