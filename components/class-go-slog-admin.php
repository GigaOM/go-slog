<?php

class GO_Slog_Admin extends GO_Slog
{
	public $current_get_vars;
	public $request = array(
		'interval' => '-1h',
		'limit'    => '50',
		'domain'   => '',
		'code'     => '',
		'message'  => '',
		'function' => '',
	);
	public $terms = array(
		'code',
		'message',
		// These terms are tags so grouping them together so the query looks nicer
		'domain',
		'function',
	);

	/**
	 * Constructor to establish ajax endpoints
	 */
	public function __construct()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	} //end __construct

	public function admin_enqueue_scripts( $current_screen )
	{
		if ( 'tools_page_go-slog-show' != $current_screen )
		{
			return;
		} // END if

		$script_config = apply_filters( 'go_config', array( 'version' => $this->version ), 'go-script-version' );

		wp_enqueue_style( 'go-slog', plugins_url( 'css/go-slog.css', __FILE__ ), array(), $script_config['version'] );
		wp_enqueue_script( 'go-slog', plugins_url( 'js/lib/go-slog.js', __FILE__ ), array( 'jquery' ), $script_config['version'] );
	} //end admin_enqueue_scripts

	public function admin_menu()
	{
		if ( ! function_exists( 'go_loggly' ) )
		{
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}//end if

		add_submenu_page( 'tools.php', 'View Slog', 'View Slog', 'manage_options', 'go-slog-show', array( $this, 'show_log' ) );
	} //end admin_menu

	/**
	 * hooked to the admin_notices action to inject a message if depenencies are not activated
	 */
	public function admin_notices()
	{
		?>
		<div class="error">
			<p>
				You must <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">activate</a> the following plugins before using the <code>go-slog</code> plugin:
			</p>
			<ul>
				<li>go-loggly</li>
			</ul>
		</div>
		<?php
	}//end admin_notices

	/**
	 * Show the contents of the log
	 *
	 * @return Null
	 */
	public function show_log()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			return;
		} //end if

		nocache_headers();

		// Update our request values
		$this->parse_request( $_GET );

		// Start the loggly search pager
		$log_query = $this->log_query();

		require_once __DIR__ . '/class-go-slog-admin-table.php';

		$go_slog_table = new GO_Slog_Admin_Table();

		// Let the table class know the results and the request
		$go_slog_table->log_query = $log_query;
		$go_slog_table->request   = $this->request;

		// Build limit options for use in the table header
		$go_slog_table->limit_options = $this->build_options(
			array(
				'25'  => '25',
				'50'  => '50',
				'100' => '100',
				'200' => '200',
				'300' => '300',
			),
			$this->request['limit']
		);

		require_once __DIR__ . '/templates/view-slog.php';
	} //end show_log

	public function parse_request( $request )
	{
		foreach ( $this->request as $key => $value )
		{
			if ( isset( $request[ $key ] ) )
			{
				$this->request[ $key ] = $request[ $key ];
			} // END if
		} // END foreach
	} // END parse_request

	/**
	 * Returns a GO_Slog_Search_Results_Pager containing relevant log entries
	 *
	 * @return GO_Slog_Search_Results_Pager log entry pager
	 */
	public function log_query()
	{
		$query = 'tag:go-slog';
		$query .= $this->parse_terms();

		$search_query = array(
			'q'     => urlencode( $query ),
			'from'  => $this->request['interval'],
			'until' => 'now',
			'size'  => $this->request['limit'],
		);

		if ( ! function_exists( 'go_loggly' ) )
		{
			return new WP_Error( 'go-slog_search_error', 'go-loggly must be activated to log and view results' );
		}//end if

		return go_loggly()->search( $search_query );
	} //end log_query

	public function parse_terms()
	{
		$query = '';

		foreach ( $this->terms as $term )
		{
			if ( ! isset( $this->request[ $term ] ) || '' == $this->request[ $term ] )
			{
				continue;
			} // END if

			if ( 'message' == $term )
			{
				$query .= ' json.message:' . $this->request[ $term ];
			} // END if
			elseif ( 'code' == $term )
			{
				$query .= ' json.code:' . $this->request[ $term ];
			} // END elseif
			else
			{
				$query .= ' tag:"' . $this->request[ $term ] . '"';
			} // END else
		} // END foreach

		return $query;
	} // END parse_terms

	public function is_search()
	{
		foreach ( $this->terms as $term )
		{
			if ( isset( $this->request[ $term ] ) && '' != $this->request[ $term ] )
			{
				return TRUE;
			} // END if
		} // END foreach

		return FALSE;
	} // END is_search

	/**
	 * Helper function to build select options
	 *
	 * @param array $options of options
	 * @param string $existing which option to preselect
	 * @return string $select_options html options
	 */
	public function build_options( $options, $existing )
	{
		$select_options = '';

		foreach ( $options as $option => $text )
		{
			$select_options .= '<option value="' . esc_attr( $option ) . '"' . selected( $option, $existing, FALSE ) . '>' . $text . "</option>\n";
		} //end foreach

		return $select_options;
	} //end build_options
}//end GO_Slog_Admin
