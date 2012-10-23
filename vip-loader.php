<?php

/**
 * Plugin name: GO Simple DB Log - VIP Loader
 * Description:	A way to log occurances to an Amazon Simple DB
 * Author: 		GigaOM 
 * Author URI: 	http://gigaom.com/
 */

require_once __DIR__ . '/components/class-go-slog.php';

$config = go_config()->load('go-slog');

new Go_Slog( $config );
