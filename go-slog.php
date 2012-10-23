<?php

/**
 * Plugin name: GO Simple DB Log
 * Description:	A way to log occurances to an Amazon Simple DB
 * Author: 		GigaOM 
 * Author URI: 	http://gigaom.com/
 */

require_once __DIR__ . '/components/class-go-slog.php';

add_action( 'plugins_loaded', function() {
	new Go_Slog();
});