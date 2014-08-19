(function($) {
	'use strict';
	$(document).on( 'change', '#go_slog_search_interval', function( e ) {
		window.location.href = $('#js_slog_url').attr('value') + '&search_interval=' + $(this).val() + '&slog_code=' + $('#slog_code').val();
	});
	$(document).on( 'click', '#filter_slog_code', function( e ) {
		window.location.href = $('#js_slog_url').attr('value') + '&search_interval=' + $('#go_slog_search_interval').val() + '&slog_code=' + $('#slog_code').val();
	});
})(jQuery);