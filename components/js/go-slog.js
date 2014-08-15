(function($) {
	'use strict';
	$(document).on( 'change', '#go_slog_search_interval', function( e ) {
		window.location.href = $('#js_slog_url').attr('value') + '&search_interval=' + $(this).val();
	});
})(jQuery);