<?php

class GO_Slog
{
	public $admin;
	public $version = '2.1';

	/**
	 * constructor to setup the simple log
	 */
	public function __construct()
	{
		if ( is_admin() )
		{
			$this->admin();
		} //end if

		add_action( 'go_slog', array( $this, 'log' ), 10, 3 );
		add_filter( 'go_slog', array( $this, 'deprecated_log' ), 10, 3 );
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

	/**
	 * This is here to continue supporting legacy apply_filters go-slog calls while noting that they are deprecated
	 */
	public function deprecated_log( $code = '', $message = '', $data = '' )
	{
		_deprecated_function( 'apply_filters:go_slog', 'Gigaom Slog v2.1', 'do_action:go_slog' );
		$this->log( $code, $message, $data );
	} // END deprecated_log

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
			'code'     => $code,
			'message'  => $message,
			 // we flatten our data here so as to not use up loggly's 150 total parsed json key limit. See https://community.loggly.com/customer/portal/questions/6544954-json-not-getting-parsed
			'data'     => serialize( $data ),
			'class'    => ( isset( $backtrace[3]['class'] ) ) ? $backtrace[3]['class'] : NULL,
			'function' => ( isset( $backtrace[3]['function'] ) ) ? $backtrace[3]['function'] : NULL,
			'from'     => ( isset( $backtrace[2]['file'], $backtrace[2]['line'] ) ) ? $backtrace[2]['file'] . ':' . $backtrace[2]['line'] : NULL,
			'domain'   => parse_url( home_url(), PHP_URL_HOST ),
			'blog'     => get_bloginfo( 'name' ),
		);

		// We need to remember going forward that tags aren't reliably placed in results (i.e. the tag for class might be returned as tag 1, 2, 3, or whatever)
		// That means if we want to display them in ways like we do for the class column we need to store them as seperate $log_item values as well
		$tags = array( 'go-slog' );
		$tags[] = $log_item['class'];
		$tags[] = $log_item['function'];
		$tags[] = $log_item['domain'];
		$tags[] = $log_item['blog'];

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