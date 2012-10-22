<?php

/**
 * Plugin name: GO Simple DB Log
 * Description:	A way to log occurances to an Amazon Simple DB
 * Author: 		GigaOM 
 * Author URI: 	http://gigaom.com/
 */

if ( $config = Go_Wp_Config::load('go-slog') )
{
	require_once __DIR__ . '/components/class-go-slog.php';

	new Go_Slog( $config['aws_access_key'], $config['aws_secret_key'], $config['aws_sdb_domain'] );
} // end if