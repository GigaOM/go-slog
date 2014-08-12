(function($) {
	'use strict';
	$(document).on( 'change', '#go_slog_search_window', function( e ) {
		window.location.href = $('#js_slog_url').attr('value') + '&search_window=' + $(this).val();
	});
})(jQuery);