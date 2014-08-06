(function($) {
	$(document).on( 'change', '#go_loggly_search_window', function( e ) {
		window.location.href = $('#js_loggly_url').attr('value') + '&search_window=' + $(this).val();
	});
})(jQuery);