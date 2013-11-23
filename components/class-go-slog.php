<?php

class GO_Slog
{
	public static $domain_suffix;
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

		static::$domain_suffix = static::get_domain_suffix();

		if ( is_admin() )
		{
			include_once __DIR__ . '/class-go-slog-admin.php';
			go_slog_admin();
			add_action( 'go_slog_cron', array( $this, 'clean_domains' ) );
		} // end if

		add_filter( 'go_slog', 'Go_Slog::log', 10, 3 );

		$this->config( apply_filters( 'go_config', FALSE, 'go-slog' ) );
	} // end __construct

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
		static::check_domains();

		$microtime = explode( ' ', microtime() );

		$log_item['log_date'] = array( 'value' => $microtime[1] . substr( $microtime[0], 1 ) );
		$log_item['host']     = array( 'value' => parse_url( site_url('/'), PHP_URL_HOST ) );
		$log_item['code']     = array( 'value' => $code );
		$log_item['message']  = array( 'value' => $message );
		$log_item['data']     = array( 'value' => serialize( $data ) );

		static::simple_db()->putAttributes( static::$config['aws_sdb_domain'] . static::$domain_suffix['curr_week'], uniqid( 'log_item_' ), $log_item );
	} // end log

	/**
	 * Ensure SimpleDB domains exist and that old ones are removed
	 */
	public static function check_domains()
	{
		$simple_db = static::simple_db();

		// Check if log domain exists and if it doesn't create it
		$domains = $simple_db->listDomains();

		// Get domain suffix
		static::$domain_suffix = static::get_domain_suffix();

		if ( $domains )
		{
			foreach ( static::$domain_suffix as $suffix )
			{
				if ( ! in_array( static::$config['aws_sdb_domain'] . $suffix, $domains ) )
				{
					$simple_db->createDomain( static::$config['aws_sdb_domain'] . $suffix );
				}//end if
			}//end foreach
		}//end if
	}//end check_domains

	/**
	 * Clean out old domains
	 */
	public function clean_domains()
	{
		$simple_db = static::simple_db();

		// Check if log domain exists and if it doesn't create it
		$domains = $simple_db->listDomains();

		// Get domain suffix
		static::$domain_suffix = static::get_domain_suffix();

		if ( $domains )
		{
			foreach ( $domains as $domain )
			{
				if (
					     preg_match( '#^' . static::$config['aws_sdb_domain'] . '_[0-9]{4}_[0-9]{2}$#', $domain )
					&& ! in_array( preg_replace( '#^' . static::$config['aws_sdb_domain'] . '#', '', $domain ), static::$domain_suffix )
				)
				{
					$simple_db->deleteDomain( $domain );
				} // END if
			} // END foreach
		} // END if
	} // END clean_domains

	/**
	 * Register a cron hook with WP so clean_domains is run once each day
	 */
	public function cron_register()
	{
		if ( ! wp_next_scheduled( 'go_slog_cron' ) )
		{
			wp_schedule_event( time(), 'daily', 'go_slog_cron' );
		} // END if
	} // END cron_register

	/**
	 * Retrieve (and generate if necessary) domain suffixes for the current and previous weeks
	 */
	public static function get_domain_suffix()
	{
		// Domains will be chunked by week of the year current/previous
		if ( ! is_array( static::$domain_suffix ) )
		{
			static::$domain_suffix = array(
				'curr_week' => date( '_Y_W' ),
				'prev_week' => date( '_Y_W', strtotime( 'last ' . date( 'l' ) ) ),
			);
		}//end if

		return static::$domain_suffix;
	}//end get_domain_suffix

	/**
	 * Return an AWS SimpleDB Object, leveraging the GO_Simple_DB::get singleton function
	 * @return SimpleDB object
	 */
	public static function simple_db()
	{
		return GO_Simple_DB::get( static::$config['aws_sdb_domain'] . static::$domain_suffix['curr_week'], static::$config['aws_access_key'], static::$config['aws_secret_key'] );
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