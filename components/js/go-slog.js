var go_slog = {};

(function($) {
	'use strict';

	go_slog.init = function() {
		go_slog.check_column();

		// Handle column changes
		$(document).on( 'change', '#slog-column', function() {
			go_slog.check_column();
		});

		// Handle interval/limit changes
		$(document).on( 'change', '#slog-interval, #slog-limit', function() {
			window.location.href = go_slog.get_js_slog_url();
		});

		// Watch for clicks on the search button
		$(document).on( 'click', '#slog-search-button', function() {
			window.location.href = go_slog.get_js_slog_url();
		});

		// Watch for user hitting Enter/Return in the search field
		$( '#slog-terms' ).keypress( function( event ) {
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
		+ '&terms=' + $('#slog-terms').val()
		+ '&column=' + $('#slog-column').val();
	};

	// Check #slog-column value and hide/show the em as needed
	go_slog.check_column = function() {
		var $column = $('#slog-column').val();

		if ( 'code' === $column || 'message' === $column ) {
			$('.slog-search-form em').hide();
		} else {
			$('.slog-search-form em').show();
		}
	};
})(jQuery);

jQuery(function($) {
	go_slog.init();
});