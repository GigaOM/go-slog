<?php

class GO_Slog_Admin extends GO_Slog
{
	public $var_dump = FALSE;
	public $search_window = '-30s';
	public $limit    = 50;
	public $current_loggly_vars;

	/**
	 * Constructor to establish ajax endpoints
	 */
	public function __construct()
	{
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	} //end __construct

	public function admin_init()
	{
		wp_enqueue_style( 'go-slog', plugins_url( 'css/go-slog.css', __FILE__ ) );
		wp_enqueue_script( 'go-loggly', plugins_url( 'js/go-loggly.js', __FILE__ ), array( 'jquery' ) );
	} //end admin_init

	public function admin_menu()
	{
		add_submenu_page( 'tools.php', 'View Loggly', 'View Loggly', 'manage_options', 'go-loggly-show', array( $this, 'show_log' ) );
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

		// engage the loggly pager
		$log_query = $this->log_query();

		// display error message and discontinue table display
		if ( is_wp_error( $log_query ) )
		{
			?>
			<div id="message" class="updated">
				<p>
					<?php echo $log_query->get_error_message(); ?>
				</p>
			</div>
			<?php
			return;
		}//end if

		// valid pager returned, continue reporting
		$this->current_loggly_vars .= '-30s' != $this->search_window ? '&search_window=' . $this->search_window : '';

		$js_loggly_url = 'tools.php?page=go-loggly-show' . preg_replace( '#&search_window=(-30s|-5m|-10m|-30m|-1h|-3h|-3h|-6h|-12h|-24h|-1w)#', '', $this->current_loggly_vars );

		require_once __DIR__ . '/class-go-slog-admin-table.php';

		$go_loggly_table = new GO_Slog_Admin_Table();

		// assign the pager to the List_Table
		$go_loggly_table->log_query = $log_query;

		?>
		<div class="wrap view-loggly">
			<?php screen_icon( 'tools' ); ?>
			<h2>
				View Loggly from
				<select name='go_loggly_search_window' class='select' id="go_loggly_search_window">
					<?php
						echo $this->build_options(
							array(
								'-30s' => 'Last 30 seconds',
								'-5m' => 'Last 5 minutes',
								'-10m' => 'Last 10 minutes',
								'-30m' => 'Last 30 minutes',
								'-1h'  => 'Last hour',
								'-3h'  => 'Last 3 hours',
								'-6h'  => 'Last 6 hours',
								'-12h' => 'Last 12 hours',
								'-24h' => 'Last day',
								'-1w'  => 'Last week',
							),
							$this->search_window
						);
					?>
				</select>
				to now
			</h2>
			<?php
			if ( isset( $_GET['loggly-cleared'] ) )
			{
				?>
				<div id="message" class="updated">
					<p>Loggly search cleared!</p>
				</div>
				<?php
			}

			$go_loggly_table->prepare_items();
			$go_loggly_table->display();
			?>
			<input type="hidden" name="js_loggly_url" value="<?php echo esc_attr( $js_loggly_url ); ?>" id="js_loggly_url" />
		</div>
		<?php
	} //end show_log

	/**
	 * Returns a GO_Slog_Search_Results_Pager containing relevant log entries
	 *
	 * @return GO_Slog_Search_Results_Pager log entry pager
	 */
	public function log_query()
	{
		$this->search_window  = isset( $_GET['search_window'] ) ? $_GET['search_window'] : $this->search_window;

		$search_query_str = sprintf(
			'tag:go-slog&from=%s&until=now',
			$this->search_window
		);

		return go_loggly()->search( $search_query_str );
	} //end log_query

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