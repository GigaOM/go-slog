<?php

/**
 * @deprecated you should be using apply_filter( 'go_slog', $code, $message, $data ); in your plugin instead
 */

/**
 * Log and return an error
 * @param $code string The code you want to log, doubles as the WP_Error code you want to return
 * @param $message string The message you want to log and return in teh WP_Error
 * @param $data string The data you want logged, also attaches to the WP_Error
 * @return WP_Error
 */
function go_slog_and_error( $code = '', $message = '', $data = '' )
{
	Go_Slog::log( $code, $message, $data );
	return new WP_Error( $code, $message, $data );
} // end go_slog_and_error

/**
 * Log and stop execution
 * @param $code string The code you want to log
 * @param $message string The message you want to log
 * @param $data string The data you want logged
 */
function go_slog_and_die( $code = '', $message = '', $data = '', $http_code = '' )
{
	Go_Slog::log( $code, $message, $data );
	new WP_Error( $code, $message, $data );
	header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' ' . $http_code . ' ' . $message, TRUE, $http_code );
	die;
} // end go_slog_and_die

/**
 * Log and return an error
 * @param $code string The code you want to log
 * @param $message string The message you want to log
 * @param $data string The data you want logged
 */
function go_slog( $code = '', $message = '', $data = '' )
{
	Go_Slog::log($code, $message, $data);
} // end go_slog
