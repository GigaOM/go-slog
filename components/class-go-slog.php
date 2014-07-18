<?php

class GO_Slog
{
	public $domain_suffix;
	public $log_on = TRUE;
	public $config = array();
	public $admin;

	/**
	 * constructor to setup the simple log
	 *
	 * @param ?? $config default to Null,
	 * @return Null
	 */
	public function __construct( $config = NULL )
	{
		if ( $this->log_on == FALSE )
		{
			return;
		} //end if

		$this->get_domain_suffix();
		$this->config( apply_filters( 'go_config', FALSE, 'go-slog' ) );

		if ( is_admin() )
		{
			$this->admin();
			$this->admin->domain_suffix = $this->domain_suffix;
			$this->admin->config = $this->config;
		} //end if

		add_action( 'go_slog_cron', array( $this, 'clean_domains' ) );
		add_filter( 'go_slog', array( $this, 'log' ), 10, 3 );
	} //end __construct

	/**
	 * Admin singleton
	 *
	 * @return $this->admin
	 */
	public function admin()
	{
		include_once __DIR__ . '/class-go-slog-admin.php';

		if ( ! is_object( $this->admin ) )
		{
			$this->admin = new GO_Slog_Admin();
		}//end if

		return $this->admin;
	} //end admin

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
		$this->config = $config;
	}//end config

	/**
	 * map go_slog calls to Loggly's 'inputs' API
	 *
	 * @param $code string The code you want to log
	 * @param $message string The message you want to log
	 * @param $data string The data you want logged
	 */
	public function log( $code = '', $message = '', $data = '' )
	{
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );

		$log_item = array(
			'code'    => $code,
			'message' => $message,
			'data'    => serialize( $data ), // we flatten our data here so as to not use up loggly's 150 total parsed json key limit. See https://community.loggly.com/customer/portal/questions/6544954-json-not-getting-parsed
			'from'    => ( isset( $backtrace[2]['file'], $backtrace[2]['line'] ) ) ? $backtrace[2]['file'] . ':' . $backtrace[2]['line'] : NULL,
		);

		$tags = array( 'go-slog' );
		$tags[] = ( isset( $backtrace[3]['class'] ) ) ? $backtrace[3]['class'] : NULL;
		$tags[] = ( isset( $backtrace[3]['function'] ) ) ? $backtrace[3]['function'] : NULL;

		$response = go_loggly()->inputs( $log_item, $tags );
	} //end log

	/**
	 * Ensure SimpleDB domains exist and that old ones are removed
	 */
	public function check_domains()
	{
		$simple_db = $this->simple_db();

		// Check if log domain exists and if it doesn't create it
		$domains = $simple_db->listDomains();

		if ( $domains )
		{
			foreach ( $this->domain_suffix as $suffix )
			{
				if ( ! in_array( $this->config['aws_sdb_domain'] . $suffix, $domains ) )
				{
					$simple_db->createDomain( $this->config['aws_sdb_domain'] . $suffix );
				}//end if
			}//end foreach
		}//end if
	}//end check_domains

	/**
	 * Clean out old domains
	 */
	public function clean_domains()
	{
		$simple_db = $this->simple_db();

		// Check if log domain exists and if it doesn't create it
		$domains = $simple_db->listDomains();

		if ( $domains )
		{
			foreach ( $domains as $domain )
			{
				if (
					     preg_match( '#^' . $this->config['aws_sdb_domain'] . '_[0-9]{4}_[0-9]{2}$#', $domain )
					&& ! in_array( preg_replace( '#^' . $this->config['aws_sdb_domain'] . '#', '', $domain ), $this->domain_suffix )
				)
				{
					$simple_db->deleteDomain( $domain );
				} //end if
			} //end foreach
		} //end if
	} //end clean_domains

	/**
	 * Register a cron hook with WP so clean_domains is run once each day
	 */
	public function cron_register()
	{
		if ( ! wp_next_scheduled( 'go_slog_cron' ) )
		{
			wp_schedule_event( time(), 'daily', 'go_slog_cron' );
		} //end if
	} //end cron_register

	/**
	 * Retrieve (and generate if necessary) domain suffixes for the current and previous weeks
	 *
	 * @return array of domain suffixes of the current and previous weeks
	 */
	public function get_domain_suffix()
	{
		// Domains will be chunked by week of the year current/previous
		if ( ! is_array( $this->domain_suffix ) )
		{
			$this->domain_suffix = array(
				'curr_week' => date( '_Y_W' ),
				'prev_week' => date( '_Y_W', strtotime( 'last ' . date( 'l' ) ) ),
			);
		}//end if

		return $this->domain_suffix;
	}//end get_domain_suffix

	/**
	 * Return an AWS SimpleDB Object, leveraging the go_simple_db() singleton function
	 *
	 * @return SimpleDB object
	 */
	public function simple_db()
	{
		return go_simple_db( $this->config['aws_sdb_domain'] . $this->domain_suffix['curr_week'], $this->config['aws_access_key'], $this->config['aws_secret_key'] );
	} //end simple_db
}//end class

/**
* Singleton
*
* @global GO_Slog $go_slog
* @return GO_Slog
*/
function go_slog()
{
	global $go_slog;

	if ( ! isset( $go_slog ) )
	{
		$go_slog = new GO_Slog();
	}//end if

	return $go_slog;
}//end go_slog