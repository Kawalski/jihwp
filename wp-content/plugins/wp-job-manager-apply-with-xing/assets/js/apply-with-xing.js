jQuery( function( $ ) {

	var online = function( session ) {
		var current_time = (new Date()).getTime() / 1000;
		return session && session.access_token && session.expires > current_time;
	};

	var xing_force = false;

	hello.init({
    	xing : apply_with_xing.consumer_key
    	},{
    	oauth_proxy : apply_with_xing.oauth_proxy,
    	redirect_uri : apply_with_xing.redirect_uri
	});

	$('textarea#apply-with-xing-cover-letter').data( 'o_text', $('textarea#apply-with-xing-cover-letter').val() );

	$('.application_button').click(function(){
		$('.wp-job-manager-application-details').slideUp();
	});

	$('input.apply-with-xing').click(function() {
		$('.application_details, .wp-job-manager-application-details').slideUp();
		$('input.apply-with-xing').attr( "disabled", "disabled" );

		if ( $('.apply-with-xing-details').is(':visible') ) {
			$('.apply-with-xing-details').slideUp();
			$('input.apply-with-xing').removeAttr( "disabled" );
		} else {
			run_apply_with_xing();
		}
		return false;
	});

	function run_apply_with_xing() {
		var xing = hello( "xing" ).getAuthResponse();

		if ( ! xing_force && online( xing ) ) {
			get_and_display_xing_profile_data();
		} else {
			hello( 'xing' ).login({ force : xing_force }).then(function(){
				xing_force = false;
				get_and_display_xing_profile_data();
			},function( r ){
				$('input.apply-with-xing').removeAttr( "disabled" );
				xing_force = true;
				console.log(r);
			});
		}
	}

	function get_and_display_xing_profile_data() {
		return hello( 'xing' ).api( 'me' ).then(function( r ) {
			display_xing_profile_data( r.users );
		},function( r ){
			xing_force = true;
			//console.log(r);
			run_apply_with_xing();
		});
	}

	function display_xing_profile_data( profile ) {
		var profile  = profile[0];
		var $profile = $('.apply-with-xing-profile');

		//console.log( profile );

		// Reset
		$profile.find('.profile-current-positions ul').empty().parent().show();
		$profile.find('.profile-past-positions ul').empty().parent().show();
		$profile.find('.profile-educations ul').empty().parent().show();
		$('textarea#apply-with-xing-cover-letter').val( $('textarea#apply-with-xing-cover-letter').data( 'o_text' ) + profile.display_name );
		$('#apply-with-xing-profile-data').val( JSON.stringify( profile, null, '' ) );

		$profile.find('.profile-name').html( profile.display_name );
		$profile.find('.profile-headline').html( profile.haves );
		$profile.find('dd.profile-email').html( profile.active_email );

		var location = '';
		var address  = false;

		if ( profile.business_address ) {
			address = profile.business_address;
		} else if ( profile.private_address ) {
			address = profile.private_address;
		}

		if ( address ) {
			if ( address.city ) {
				location = address.city + ', ';
			}
			location = location + address.country;
		}

		$profile.find('.profile-location').html( location );

		if ( profile.photo_urls ) {
			$profile.find('img').attr('src', profile.photo_urls.size_96x96 );
			$profile.find('img').attr('alt', profile.display_name );
		} else {
			$profile.find('img').hide();
		}

		if ( profile.professional_experience ) {
			if ( profile.professional_experience.primary_company ) {
				var company = profile.professional_experience.primary_company;
				var company_html = '<li><strong>' + company.title + "</strong> - " + company.name;
				if ( company.description ) {
					company_html = company_html + "<br/>" + company.description;
				}
				company_html = company_html + '</li>';
				$profile.find( 'dd.profile-current-positions ul' ).append( company_html );
			} else {
				$profile.find('.profile-current-positions').hide();
			}

			if ( profile.professional_experience.non_primary_companies ) {
				$( profile.professional_experience.non_primary_companies ).each( function( index ) {
					var company = profile.professional_experience.non_primary_companies[ index ];
					var company_html = '<li><strong>' + company.title + "</strong> - " + company.name;
					if ( company.description ) {
						company_html = company_html + "<br/>" + company.description;
					}
					company_html = company_html + '</li>';
					$profile.find( 'dd.profile-past-positions ul' ).append( company_html );
				});
			} else {
				$profile.find('.profile-past-positions').hide();
			}
		} else {
			$profile.find('.profile-current-positions').hide();
			$profile.find('.profile-past-positions').hide();
		}

		if ( profile.educational_background ) {
			if ( profile.educational_background.schools ) {
				$( profile.educational_background.schools ).each( function( index ) {
					var school = profile.educational_background.schools[ index ];
					var school_html = '<li><strong>' + school.name + '</strong>';
					if ( school.end_date ) {
						school_html = school_html + " (" + school.end_date + ")";
					}
					if ( school.subject ) {
						school_html = school_html + "<br/>" + school.subject;
					}
					if ( school.degree ) {
						school_html = school_html + " - " + school.degree;
					}
					school_html = school_html + '</li>';
					$profile.find( 'dd.profile-educations ul' ).append( school_html );
				});
			} else {
				$profile.find('.profile-educations').hide();
			}
		} else {
			$profile.find('.profile-educations').hide();
		}

		$('.apply-with-xing-details').slideDown().triggerHandler('wp-job-manager-application-details-show');
		$('input.apply-with-xing').removeAttr( "disabled" );
	}
});