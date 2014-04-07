(function($) {
    $(document).on( 'change', '#go_slog_limit', function( e ) {
		window.location.href = $('#js_slog_url').attr('value') + '&week=' + $('#go_slog_week').val() + '&limit=' + $(this).val();
     });

     $(document).on( 'change', '#go_slog_week', function( e ) {
 		window.location.href = $('#js_slog_url').attr('value') + '&week=' + $(this).val() + '&limit=' + $('#go_slog_limit').val();
      });
})(jQuery);