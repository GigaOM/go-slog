(function($) {
    $(document).on( 'change', '#go_slog_limit', function( e ) {
		window.location.href = $('#js_slog_url').attr('value') + '&limit=' + $(this).val();
     });
})(jQuery);