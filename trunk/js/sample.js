function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
		vars[key] = value;
	});
	return vars;
}

$('#clickme').click( function(e) {
	e.preventDefault();
	var path = decodeURIComponent( getUrlVars()['cb'] );
	jQuery("body").append('<iframe src="'+path+'" height="0" width="0"></iframe>');
} );