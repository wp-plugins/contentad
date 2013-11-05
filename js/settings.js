jQuery(document).ready(function($){

	// Reload the page after closing thickbox
	$(document).bind('tb_unload', function(){
		location.reload(true);
	});

	// Convert percentage height values to correct pixel dimensions based on users viewport
	function resizeThickbox() {
		$.each( $('a.thickbox'), function(){
			var $this = $(this);
			
			var height = parseInt( $(window).height() * 0.8 );
			var width = parseInt( $(window).width() * 0.8 );
			if ( width > 960 ) {
				width = 960;
			}
			
			var qsParams = $this.attr('href').split('?');
			var qsParamsSplit = qsParams[1].split('&');
			
			$.each( qsParamsSplit, function(index) {
				if ( /^width=.*$/.test(this) ) {
					qsParamsSplit[index] = "width=" + width;
				}
				if ( /^height=.*$/.test(this) ) {
					qsParamsSplit[index] = "height=" + height;
				}
			});
			
			var href = qsParams[0] + "?" + qsParamsSplit.join("&");
			$this.attr( 'href', href );
		} );
	}
	
	resizeThickbox();

	$(window).resize(function() {
		resizeThickbox();
	});
});