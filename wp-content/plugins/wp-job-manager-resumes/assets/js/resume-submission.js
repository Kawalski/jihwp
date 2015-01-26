jQuery(document).ready(function($) {
	jQuery( '.resume-manager-add-row' ).click(function() {
		jQuery(this).before( jQuery(this).data('row') );
		return false;
	});
	jQuery( '#submit-resume-form' ).on('click', '.resume-manager-remove-row', function() {
		if ( confirm( resume_manager_resume_submission.i18n_confirm_remove ) ) {
			jQuery(this).closest( 'div.resume-manager-data-row' ).remove();
		}
		return false;
	});
	jQuery( '.job-manager-remove-uploaded-file' ).click(function() {
		jQuery(this).closest( '.job-manager-uploaded-file' ).remove();
		return false;
	});
	jQuery('.fieldset-candidate_experience .field, .fieldset-candidate_education .field, .fieldset-links .field').sortable({
		items:'.resume-manager-data-row',
		cursor:'move',
		axis:'y',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
	});

	// Confirm navigation
	var confirm_nav = false;

	if ( jQuery('form#resume_preview').size() ) {
		confirm_nav = true;
	}
	jQuery( 'form' ).on('change', 'input', function() {
		confirm_nav = true;
	});
	jQuery( 'form' ).submit(function(){
		confirm_nav = false;
		return true;
	});
	jQuery(window).bind('beforeunload', function(event) {
		if ( confirm_nav ) {
			return resume_manager_resume_submission.i18n_navigate;
		}
	});

	// Linkedin import
	jQuery('input.import-from-linkedin').click(function() {
		if ( IN.User.isAuthorized() ) {
			import_linkedin_resume_data();
		} else {
			IN.Event.on( IN, "auth", import_linkedin_resume_data );
			IN.UI.Authorize().place();
		}
		return false;
	});

	function import_linkedin_resume_data() {
		jQuery( 'fieldset.import-from-linkedin' ).remove();
		IN.API.Profile("me")
			.fields(
				[
					"firstName",
					"lastName",
					"formattedName",
					"headline",
					"summary",
					"specialties",
					"associations",
					"interests",
					"pictureUrl",
					"publicProfileUrl",
					"emailAddress",
					"location:(name)",
					"dateOfBirth",
					"threeCurrentPositions:(title,company,summary,startDate,endDate,isCurrent)",
					"threePastPositions:(title,company,summary,startDate,endDate,isCurrent)",
					"positions:(title,company,summary,startDate,endDate,isCurrent)",
					"educations:(schoolName,degree,fieldOfStudy,startDate,endDate,activities,notes)",
					"skills:(skill)",
					"phoneNumbers",
					"primaryTwitterAccount",
					"memberUrlResources"
				]
			)
			.result( function( result ) {
				var profile = result.values[0];
				$form       = jQuery( '#submit-resume-form' );

				$form.find('input[name="candidate_name"]').val( profile.formattedName );
				$form.find('input[name="candidate_email"]').val( profile.emailAddress );
				$form.find('input[name="candidate_title"]').val( profile.headline );
				$form.find('input[name="candidate_location"]').val( profile.location.name );
				$form.find('textarea[name="resume_content"]').val( profile.summary );

				if ( jQuery.type( tinymce ) === 'object' ) {
					tinymce.get('resume_content').setContent( profile.summary );
				}

				jQuery( profile.skills.values ).each( function( i, e ) {
					if ( $form.find('input[name="resume_skills"]').val() ) {
						$form.find('input[name="resume_skills"]').val( $form.find('input[name="resume_skills"]').val() + ', ' + e.skill.name );
					} else {
						$form.find('input[name="resume_skills"]').val( e.skill.name );
					}
				});

				jQuery( profile.memberUrlResources.values ).each( function( i, e ) {
					jQuery( '.fieldset-links' ).find( '.resume-manager-add-row' ).click();
					jQuery( '.fieldset-links' ).find( 'input[name^="link_name"]' ).last().val( e.name );
					jQuery( '.fieldset-links' ).find( 'input[name^="link_url"]' ).last().val( e.url );
				});

				jQuery( profile.educations.values ).each( function( i, e ) {
					var qual = [];
					var date = [];

					if ( e.fieldOfStudy ) qual.push( e.fieldOfStudy );
					if ( e.degree ) qual.push( e.degree );
					if ( e.startDate ) date.push( e.startDate.year );
					if ( e.endDate ) date.push( e.endDate.year );

					jQuery( '.fieldset-candidate_education' ).find( '.resume-manager-add-row' ).click();
					jQuery( '.fieldset-candidate_education' ).find( 'input[name^="candidate_education_location"]' ).last().val( e.schoolName );
					jQuery( '.fieldset-candidate_education' ).find( 'input[name^="candidate_education_qualification"]' ).last().val( qual.join( ', ' ) );
					jQuery( '.fieldset-candidate_education' ).find( 'input[name^="candidate_education_date"]' ).last().val( date.join( '-' ) );
					jQuery( '.fieldset-candidate_education' ).find( 'textarea[name^="candidate_education_notes"]' ).last().val( e.notes );
				});

				jQuery( profile.positions.values ).each( function( i, e ) {
					var date = [];

					if ( e.startDate ) date.push( e.startDate.year );
					if ( e.endDate ) date.push( e.endDate.year );

					jQuery( '.fieldset-candidate_experience' ).find( '.resume-manager-add-row' ).click();
					jQuery( '.fieldset-candidate_experience' ).find( 'input[name^="candidate_experience_employer"]' ).last().val( e.company.name );
					jQuery( '.fieldset-candidate_experience' ).find( 'input[name^="candidate_experience_job_title"]' ).last().val( e.title );
					jQuery( '.fieldset-candidate_experience' ).find( 'input[name^="candidate_experience_date"]' ).last().val( date.join( '-' ) );
					jQuery( '.fieldset-candidate_experience' ).find( 'textarea[name^="candidate_experience_notes"]' ).last().val( e.summary );
				});

				$form.trigger( 'linkedin_import', profile );
			}
		);
	}
});