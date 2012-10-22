<?php

class Go_Slog
{
	public static $log_on = TRUE;
	
	public static $aws_access_key = '';
	public static $aws_secret_key = '';
	public static $aws_sdb_domain = '';
	
	public static $simple_db;
	
	/**
	 * constructor to setup the simple log
	 * @param $aws_access_key string The Amazon Web Services access key
	 * @param $aws_secret_key string The Amazon Web Services secret key
	 * @param $aws_sdb_domain string The Amazon Web Services SimpleDB domain
	 */
	public function __construct( $aws_access_key = '', $aws_secret_key = '', $aws_sdb_domain = '' )
	{
		if ( static::$log_on == FALSE )
		{
			return;
		} // end if
				
		if ( ! empty( $aws_access_key ) )
		{
			static::$aws_access_key = $aws_access_key;
		} // end if
		
		if ( ! empty( $aws_secret_key ) )
		{
			static::$aws_secret_key = $aws_secret_key;
		} // end if
		
		if ( ! empty( $aws_sdb_domain ) )
		{
			static::$aws_sdb_domain = $aws_sdb_domain;
		} // end if
				
		if ( is_admin() )
		{
			require_once __DIR__ . '/go-slog-admin.php';
			new Go_Slog_Admin();
		} // end if
		
		add_filter('go_slog', 'Go_Slog::log', 10, 3);
	} // end function __construct
	
	/**
	 * Check if the SimpleDB domain exists, if not, create it
	 */
	public static function check_domain()
	{
		// Check if log domain exists and if it doesn't create it
		$domains = static::simple_db()->listDomains();
		$exists  = FALSE;
		
		if ( $domains )
		{
			foreach ( $domains as $domain )
			{
				if ( $domain == static::$aws_sdb_domain ) 
				{
					$exists = TRUE;
					break;
				} // end if
			} // end foreach
		} // end if
		
		if ( $exists == FALSE )
		{
			static::simple_db()->createDomain( static::$aws_sdb_domain );
		} // end if
	} // end function check_domain
	
	/**
	 * Setup and return an AWS SimpleDB Object, act as a singleton
	 * @return SimpleDB object
	 */
	public static function simple_db()
	{		
		if ( ! is_object( static::$simple_db ) )
		{
	 		require_once __DIR__ . '/external/php_sdb2/SimpleDB.php';
			
			static::$simple_db = new SimpleDB( static::$aws_access_key, static::$aws_secret_key );
		} // end if
		
		return static::$simple_db;
	} // end function go_get_simple_db
	
	/**
	 * log to SimpleDB
	 * @param $code string The code you want to log
	 * @param $message string The message you want to log
	 * @param $data string The data you want logged
	 */
	public static function log( $code = '', $message = '', $data = '' )
	{
		static::check_domain();
		
		$microtime = explode( ' ', microtime() );
				
		$log_item['log_date'] = array( 'value' => $microtime[1] . substr( $microtime[0], 1 ) );
		$log_item['host'] = array( 'value' => parse_url( site_url('/') , PHP_URL_HOST ) );
		$log_item['code'] = array( 'value' => $code );
		$log_item['message'] = array( 'value' => $message );
		$log_item['data'] = array( 'value' => serialize( $data) );
		
		static::simple_db()->putAttributes( static::$aws_sdb_domain, uniqid( 'log_item_' ), $log_item );
	} // end function log
} // end class
