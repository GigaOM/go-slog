<?php

class GO_Slog
{
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

		$this->config( apply_filters( 'go_config', FALSE, 'go-slog' ) );

		if ( is_admin() )
		{
			$this->admin();
			$this->admin->config = $this->config;
		} //end if

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
	 *     customer_token string The loggly customer token
	 *     inputs_subdomain string The loggly subdomain that makes up the 'inputs' REST URL
	 *     account string The loggly account name
	 *     username string The loggly user name - goes into basic http auth header
	 *     password string The loggly password - goes into basic http auth header
	 *
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