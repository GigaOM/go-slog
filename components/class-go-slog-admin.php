<?php

class GO_Slog_Admin extends GO_Slog
{
	public $var_dump = FALSE;
	public $search_interval = '-30s';
	public $limit    = 50;
	public $current_slog_vars;

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
		wp_enqueue_script( 'go-slog', plugins_url( 'js/go-slog.js', __FILE__ ), array( 'jquery' ) );
	} //end admin_init

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

		// engage the loggly search pager
		$log_query = $this->log_query();

		// display error message and discontinue table display
		if ( is_wp_error( $log_query ) )
		{
			?>
			<div id="message" class="error">
				<p>
					<?php echo esc_html( $log_query->get_error_message() ); ?>
				</p>
			</div>
			<?php
			return;
		}//end if

		// valid pager returned, continue reporting
		$this->current_slog_vars .= '-30s' != $this->search_interval ? '&search_interval=' . $this->search_interval : '';

		$js_slog_url = 'tools.php?page=go-slog-show' . preg_replace( '#&search_interval=(-30s|-5m|-10m|-30m|-1h|-3h|-3h|-6h|-12h|-24h|-1w)#', '', $this->current_slog_vars );

		require_once __DIR__ . '/class-go-slog-admin-table.php';

		$go_slog_table = new GO_Slog_Admin_Table();

		// assign the pager to the List_Table
		$go_slog_table->log_query = $log_query;

		?>
		<div class="wrap view-slog">
			<div class="slog-report-header">
				<h2>
					View Slog From
					<select name='go_slog_search_interval' class='select' id="go_slog_search_interval">
						<?php
							echo $this->build_options(
								array(
									'-30s' => 'Last 30 seconds',
									'-5m'  => 'Last 5 minutes',
									'-10m' => 'Last 10 minutes',
									'-30m' => 'Last 30 minutes',
									'-1h'  => 'Last hour',
									'-3h'  => 'Last 3 hours',
									'-6h'  => 'Last 6 hours',
									'-12h' => 'Last 12 hours',
									'-24h' => 'Last day',
									'-1w'  => 'Last week',
								),
								$this->search_interval
							);
						?>
					</select>
					- Now
				</h2>
					<div class="slog_filter_code_action">
						<?php
						$slog_code = isset( $_REQUEST['slog_code'] ) ? $_REQUEST['slog_code'] : '';
						?>
						<p>
							<input type="button" class="primary button" id="filter_slog_code" name="filter_slog_code" value="Filter By Slog Code:" >
							<input type="text" id="slog_code" name="slog_code" value="<?php echo esc_attr( $slog_code ); ?>" >
						</p>
					</div>

			</div>
			<div class="display_slog_results">
				<?php
					$go_slog_table->prepare_items();
					$go_slog_table->custom_display();
				?>
			</div>
			<input type="hidden" name="js_slog_url" value="<?php echo esc_attr( $js_slog_url ); ?>" id="js_slog_url" />
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
		$this->search_interval  = isset( $_GET['search_interval'] ) ? $_GET['search_interval'] : $this->search_interval;
		$terms = isset( $_REQUEST['slog_code'] ) ? $_REQUEST['slog_code'] : '';

		$search_query = array(
			'q'     => urlencode( 'tag:go-slog json.code:' . $terms ),
			'from'  => $this->search_interval,
			'until' => 'now',
		);

		return go_loggly()->search( $search_query );
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