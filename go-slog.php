<?php
/**
 * Plugin name: GO Simple DB Log
 * Description:	A way to log occurances to an Amazon Simple DB
 * Author: 		GigaOM 
 * Author URI: 	http://gigaom.com/
 */

if ( !function_exists( 'go_get_simple_db' ) )
{
function go_get_simple_db($aws_access_key, $aws_secret_key)
{
	global $simple_db;
	
	if( !is_object( $simple_db ) )
	{
 			require_once dirname( __FILE__ ).'/php_sdb2/SimpleDB.php';
		
		$simple_db = new SimpleDB( $aws_access_key, $aws_secret_key );
	}
	
	return $simple_db;
}
}

function go_slog_and_error( $code = '', $message = '', $data = '' )
{
	Go_Slog::log($code, $message, $data);
	return new WP_Error( $code , $message , $data );
}

function go_slog_and_die( $code = '', $message = '', $data = '' , $http_code )
{
	Go_Slog::log($code, $message, $data);
	new WP_Error( $code , $message , $data );
	header( $_SERVER[ 'SERVER_PROTOCOL' ] .' '.$http_code .' '. $message , TRUE , $http_code );
	die;
}

function go_slog( $code = '', $message = '', $data = '' )
{
	Go_Slog::log($code, $message, $data);
}

class Go_Slog
{
	static $log_on = TRUE;
	
	static $aws_access_key = '';
	static $aws_secret_key = '';
	static $aws_sdb_domain = '';
	
	public function __construct($aws_access_key = '', $aws_secret_key = '', $aws_sdb_domain = '')
	{				
		if (self::$log_on == FALSE)
			return;		
				
		if ( !empty($aws_access_key) )
		{
			self::$aws_access_key = $aws_access_key;
		}
		
		if ( !empty($aws_secret_key) )
		{
			self::$aws_secret_key = $aws_secret_key;
		}
		
		if ( !empty( $aws_sdb_domain ) )
		{
			self::$aws_sdb_domain = $aws_sdb_domain;
		}
				
		if (is_admin())
			require_once dirname(__FILE__).'/go-slog-admin.php';
		
		add_filter('go_slog', 'Go_Slog::log', 10, 3);
	}
	// END __construct
	
	static function check_domain()
	{
		$simple_db = go_get_simple_db( self::$aws_access_key, self::$aws_secret_key );
				
		// Check if log domain exists and if it doesn't create it
		$domains = $simple_db->listDomains();
		$exists  = FALSE;
		
		if ( $domains )
		{
			foreach ( $domains as $domain )
			{
				if ( $domain == self::$aws_sdb_domain ) {
					$exists = TRUE;
					break;
				}
			}
		}
		
		if ($exists == FALSE)
		{
			$simple_db->createDomain( self::$aws_sdb_domain );
		}
	}
	// END check_domain
	
	static function log($code = '', $message = '', $data = '')
	{
		self::check_domain();
		
		$simple_db = go_get_simple_db( self::$aws_access_key, self::$aws_secret_key );
				
		$microtime = explode( ' ', microtime() );
				
		$log_item['log_date'] = array( 'value' => $microtime[1].substr($microtime[0], 1) );
		$log_item['host'] = array( 'value' => parse_url( site_url('/') , PHP_URL_HOST ) );
		$log_item['code'] = array( 'value' => $code );
		$log_item['message'] = array( 'value' => $message );
		$log_item['data'] = array( 'value' => serialize($data) );
		
		$simple_db->putAttributes( self::$aws_sdb_domain, uniqid( 'log_item_' ), $log_item );
	}
	// END log
}
// END Go_Slog