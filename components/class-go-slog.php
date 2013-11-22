<?php

class GO_Slog
{
	public static $log_on = TRUE;
	public static $config = array();

	/**
	 * constructor to setup the simple log
	 */
	public function __construct( $config = NULL )
	{
		if ( static::$log_on == FALSE )
		{
			return;
		} // end if

		if ( is_admin() )
		{
			include_once __DIR__ . '/class-go-slog-admin.php';
			go_slog_admin();
		} // end if

		add_filter( 'go_slog', 'Go_Slog::log', 10, 3 );
		add_action( 'wp_ajax_go-slog-clean', array( $this, 'clean_log' ) );
		add_action( 'wp_ajax_nopriv_go-slog-clean', array( $this, 'clean_log' ) );

		$this->config( apply_filters( 'go_config', FALSE, 'go-slog' ) );
	} // end __construct

	public function clean_log()
	{
		go_slog_admin()->clean_log();
		echo TRUE;
		die;
	} // END clean_log

	/*
	 * Setup the simple log with connectivity to AWS
	 *
	 * @param array $config should contain the following keys:
	 *     aws_access_key string The Amazon Web Services access key
	 *     aws_secret_key string The Amazon Web Services secret key
	 *     aws_sdb_domain string The Amazon Web Services SimpleDB domain
	 */
	public function config( $config )
	{
		static::$config = $config;
	}// end config

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
		$log_item['host']     = array( 'value' => parse_url( site_url('/'), PHP_URL_HOST ) );
		$log_item['code']     = array( 'value' => $code );
		$log_item['message']  = array( 'value' => $message );
		$log_item['data']     = array( 'value' => serialize( $data ) );

		static::simple_db()->putAttributes( static::$config['aws_sdb_domain'], uniqid( 'log_item_' ), $log_item );
	} // end log

	/**
	 * Return an AWS SimpleDB Object, leveraging the GO_Simple_DB::get singleton function
	 * @return SimpleDB object
	 */
	public static function simple_db()
	{
		return GO_Simple_DB::get( static::$config['aws_sdb_domain'], static::$config['aws_access_key'], static::$config['aws_secret_key'] );
	} // end simple_db
}// end class


function go_slog()
{
	global $go_slog;

	if ( ! isset( $go_slog ) )
	{
		$go_slog = new GO_Slog();
	}// end if

	return $go_slog;
}// end go_slog