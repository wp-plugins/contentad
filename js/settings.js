jQuery(document).ready(function($){

	// Reload the page after closing thickbox
	$(document).bind('tb_unload', function(){
		location.reload(true);
	});

	// Convert percentage height values to correct pixel dimensions based on users viewport
	$.each( $('a.thickbox'), function(){
		var href = $(this).attr('href').replace( /height=85%25/i, 'height=' + parseInt( $(window).height() * 0.85 ) );
		$(this).attr( 'href', href );
	} );

});