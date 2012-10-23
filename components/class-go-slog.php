<?php

class Go_Slog
{
	public static $log_on = TRUE;
	public static $config = array();

	/**
	 * constructor to setup the simple log
	 * @param $aws_access_key string The Amazon Web Services access key
	 * @param $aws_secret_key string The Amazon Web Services secret key
	 * @param $aws_sdb_domain string The Amazon Web Services SimpleDB domain
	 */
	public function __construct( $config = null )
	{
		if ( static::$log_on == FALSE )
		{
			return;
		} // end if

		static::$config = $config;

		if ( is_admin() )
		{
			require_once __DIR__ . '/class-go-slog-admin.php';
			new Go_Slog_Admin();
		} // end if

		add_filter( 'go_slog', 'Go_Slog::log', 10, 3 );
	} // end function __construct

	/**
	 * log to SimpleDB
	 * @param $code string The code you want to log
	 * @param $message string The message you want to log
	 * @param $data string The data you want logged
	 */
	public static function log( $code = '', $message = '', $data = '' )
	{
		$microtime = explode( ' ', microtime() );

		$log_item['log_date'] = array( 'value' => $microtime[1] . substr( $microtime[0], 1 ) );
		$log_item['host'] = array( 'value' => parse_url( site_url('/') , PHP_URL_HOST ) );
		$log_item['code'] = array( 'value' => $code );
		$log_item['message'] = array( 'value' => $message );
		$log_item['data'] = array( 'value' => serialize( $data ) );

		static::simple_db()->putAttributes( static::$config['aws_sdb_domain'], uniqid( 'log_item_' ), $log_item );
	} // end function log

	/**
	 * Return an AWS SimpleDB Object, leveraging the GO_Simple_DB::get singleton function
	 * @return SimpleDB object
	 */
	public static function simple_db()
	{
		return GO_Simple_DB::get( static::$config['aws_sdb_domain'], static::$config['aws_access_key'], static::$config['aws_secret_key'] );
	} // end function simple_db
} // end class
