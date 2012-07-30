jQuery(document).ready(function($) {

	var addNew = $('.wrap h2 a.add-new-h2').attr('href', ContentAd.newWidgetCall).addClass('thickbox button').show();

	addNew.clone().appendTo('.wrap h2').html(ContentAd.reportName).attr('href', ContentAd.reportCall);

	$('<a href="' + ContentAd.settingsCall + '" class="add-new-h2 thickbox button">' + ContentAd.settingsLinkText + '</a>').appendTo('.wrap h2').show();

	// Convert percentage height values to correct pixel dimensions based on users viewport
    $.each( $('a.thickbox'), function(){
		var href = $(this).attr('href').replace( /height=85%25/i, 'height=' + parseInt( $(window).height() * 0.85 ) );
		$(this).attr( 'href', href );
	} );

	// Reload the page after closing thickbox
	$(document).bind('tb_unload', function(){
		location.reload(true);
	});

	// AJAX call to delete ad widget
	$('.row-actions .trash a.submitdelete').live('click', function(e){
		e.preventDefault();
		var tableRow = $(this).closest('tr');
		tableRow.hide();
		$.post(
				ajaxurl,
				{
					action	: ContentAd.action,
					nonce  	: ContentAd.nonce,
					post_id : $(this).attr('data-postid')
				},
				function( response ){
					//console.log(response);
					if( 'success' == response.status ){
						tableRow.remove();
					}
				},
				'json'
		);

	});

    $( 'tr.inline-edit-row' ).removeClass( 'inline-edit-row-page' ).addClass( 'inline-edit-row-post' );
    $( 'tr.inline-edit-row' ).removeClass( 'quick-edit-row-page' ).addClass( 'quick-edit-row-post' );

    $( 'tr.inline-edit-row fieldset.inline-edit-col-left:first' ).remove();
    $( 'tr.inline-edit-row fieldset.inline-edit-col-right:first' ).remove();

	$('a.editinline').live('click', function(){
		var id = inlineEditPost.getId(this);
		var post_title = $( '#inline_' + id + ' .post_title' ).text();
		var placement = $('#inline_' + id + ' .placement').text();
		var excCategories = $('#inline_' + id + ' .excluded_categories').text();
		var excTags = $('#inline_' + id + ' .excluded_tags').text();

		// Assign widget title
		$( 'tr.inline-edit-row h4.contentad-widget-title'  ).html( post_title );

		// Assign Placement
		if( 'in_widget' == placement ) {
			$( '#in_widget' ).attr('checked', 'checked');
		} else if ( 'before_post_content' == placement ) {
			$( '#before_post_content' ).attr('checked', 'checked');
		} else {
			$( '#after_post_content' ).attr('checked', 'checked');
		}

		// Assign categories
		$.each( $('#inline-edit .inline-edit-categories input[type="checkbox"]'), function() {
			if( excCategories && $.inArray( $(this).val(), excCategories.split(',') ) != -1 ) {
				$( '#in-category-' + $(this).val() ).attr('checked', 'checked');
			} else {
				$( '#in-category-' + $(this).val() ).removeAttr('checked');
			}
		} );

		// Assign tags
		if( excTags ) {
			$('#contentad_exc_tags').html( excTags );
		} else {
			$('#contentad_exc_tags').html( '' );
		}
	} );

});