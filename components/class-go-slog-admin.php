<?php

class GO_Slog_Admin extends GO_Slog
{
	public $current_get_vars;
	public $request = array(
		'interval' => '-1h',
		'limit'    => '50',
		'terms'    => '',
		'column'   => 'code',
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
		wp_enqueue_script( 'go-slog', plugins_url( 'js/go-slog.js', __FILE__ ), array( 'jquery' ), $script_config['version'] );
	} //end admin_enqueue_scripts

	public function admin_menu()
	{
		add_submenu_page( 'tools.php', 'View Slog', 'View Slog', 'manage_options', 'go-slog-show', array( $this, 'show_log' ) );
	} //end admin_menu

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

		if ( '' != $this->request['terms'] )
		{
			$query .= $this->parse_terms();
		} // END if

		$search_query = array(
			'q'     => urlencode( $query ),
			'from'  => $this->request['interval'],
			'until' => 'now',
			'size'  => $this->request['limit'],
		);
		//print_r($search_query); exit();
		return go_loggly()->search( $search_query );
	} //end log_query

	public function parse_terms()
	{
		$query = '';

		switch ( $this->request['column'] )
		{
			case 'message':
				$query .= ' json.message:' . $this->request['terms'];
				break;
			case 'code':
				$query .= ' json.code:' . $this->request['terms'];
				break;
			default:
				$query .= ' tag:"' . $this->request['terms'] . '"';
				break;
		} // END switch

		return $query;
	} // END parse_terms

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