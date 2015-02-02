var go_slog = {};

(function($) {
	'use strict';

	go_slog.init = function() {
		// Handle interval/limit changes
		$(document).on( 'change', '#slog-interval, #slog-limit', function() {
			window.location.href = go_slog.get_js_slog_url();
		});

		// Watch for clicks on the search button
		$(document).on( 'click', '#slog-search-button', function() {
			window.location.href = go_slog.get_js_slog_url();
		});

		// Watch for user hitting Enter/Return in the search field
		$( '#slog-limit, #slog-interval, #slog-domain, #slog-code, #slog-message, #slog-function' ).keypress( function( event ) {
			if ( 13 === event.keyCode) {
				event.preventDefault();
				window.location.href = go_slog.get_js_slog_url();
			}
		});
	};

	// Retrieve all of the field values and return a valid URL for use in the form
	go_slog.get_js_slog_url = function() {
		return 'tools.php?page=go-slog-show'
		+ '&limit=' + $('#slog-limit').val()
		+ '&interval=' + $('#slog-interval').val()
		+ '&domain=' + $('#slog-domain').val()
		+ '&code=' + $('#slog-code').val()
		+ '&message=' + $('#slog-message').val()
		+ '&function=' + $('#slog-function').val();
	};
})(jQuery);

jQuery(function($) {
	go_slog.init();
});