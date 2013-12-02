<?php

class GO_Slog_Admin
{
	public $var_dump = FALSE;
	public $week     = 'curr_week';
	public $limit    = 100;
	public $limits   = array(
		'100'  => '100',
		'250'  => '250',
		'500'  => '500',
		'1000' => '1000',
	);
	public $current_slog_vars;
	public $domain_suffix;

	/**
	 * Constructor to establish a couple ajax endpoints
	 */
	public function __construct()
	{
		$this->domain_suffix = GO_Slog::get_domain_suffix();

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_go-slog-clear', array( $this, 'clear_log' ) );
		add_action( 'wp_ajax_go-slog-csv', array( $this, 'export_csv' ) );
		add_action( 'wp_ajax_go-slog-cron-register', array( $this, 'cron_register_admin_ajax' ) );
	} // end __construct

	public function admin_init()
	{
		wp_enqueue_style( 'go-slog', plugins_url( 'css/go-slog.css', __FILE__ ) );
		wp_enqueue_script( 'go-slog', plugins_url( 'js/go-slog.js', __FILE__ ), array( 'jquery' ) );
	} // END admin_init

	public function admin_menu()
	{
		add_submenu_page( 'tools.php', 'View Slog', 'View Slog', 'manage_options', 'go-slog-show', array( $this, 'show_log') );
	} // END admin_menu

	/**
	 * Delete all entries in the log
	 */
	public function clear_log()
	{
		if (
			   ! current_user_can( 'manage_options' )
			|| ! isset( $_REQUEST['_wpnonce'] )
			|| ! isset( $_REQUEST['week'] )
			|| ! isset( $this->domain_suffix[ $_REQUEST['week'] ] )
			|| ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'go_slog_clear' )
		)
		{
			wp_die( 'Not cool', 'Unauthorized access', array( 'response' => 401 ) );
		} // end if

		GO_Slog::simple_db()->deleteDomain( GO_Slog::$config['aws_sdb_domain'] . $this->domain_suffix[ $_REQUEST['week'] ] );

		wp_redirect( admin_url( 'tools.php?page=go-slog-show&slog-cleared=yes' ) );
		die;
	} // end clear_log

	/**
	 * Cleans log of items older than 10 days
	 */
	public function clean_log()
	{
		// Amazon SDB won't delete records conditionally so we are left with this
		$expired_log_items = GO_Slog::simple_db()->select(
			'SELECT itemName FROM `' . GO_Slog::$config['aws_sdb_domain'] . "` WHERE log_date < '"
			. strtotime( '-1 week' ) . ".00000000' ORDER BY log_date ASC LIMIT 1000",
			NULL
		);

		$expired_log_items = array_keys( $expired_log_items );

		// Amazon SDB only allows up to 25 items at a time to be deleted so we get to have fun here
		$chunk = array();

		foreach ( $expired_log_items as $item_name )
		{
			$chunk[ $item_name ] = NULL;

			if ( 25 == count( $chunk ) )
			{
				GO_Slog::simple_db()->batchDeleteAttributes( GO_Slog::$config['aws_sdb_domain'], $chunk );
				$chunk = array();
			} // END if
		} // END foreach
	} // END clean_log

	/**
	 * Formats data for output
	 * @param $data array
	 * @return string formatted data
	 */
	public function format_data( $data )
	{
		if ( $this->var_dump == FALSE )
		{
			$data = print_r( unserialize( $data ), TRUE );
		} // end if
		else
		{
			ob_start();
			var_dump( unserialize( $data ) );
			$data = ob_get_clean();
		} // end else

		return $data;
	} // end format_data

	/**
	 * Show the contents of the log
	 */
	public function show_log()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			return;
		} // end if

		nocache_headers();

		$this->var_dump = isset( $_GET['var_dump'] ) ? TRUE : FALSE;

		$log_query = $this->log_query();

		$this->current_slog_vars = $this->var_dump ? '&var_dump=yes' : '';
		$this->current_slog_vars .= 100 != $this->limit ? '&limit=' . $this->limit : '';
		$this->current_slog_vars .= 'curr_week' != $this->week ? '&week=' . $this->week : '';
		$this->current_slog_vars .= isset( $_REQUEST['host'] ) && '' != $_REQUEST['host'] ? '&host=' . $_REQUEST['host'] : '';
		$this->current_slog_vars .= isset( $_REQUEST['code'] ) && '' != $_REQUEST['code'] ? '&code=' . $_REQUEST['code'] : '';
		// Handle the two cases of a message value seperately
		$this->current_slog_vars .= isset( $_POST['message'] ) && '' != $_POST['message'] ? '&message=' . base64_encode( $_POST['message'] ) : '';
		$this->current_slog_vars .= isset( $_GET['message'] ) && '' != $_GET['message'] ? '&message=' . $_GET['message'] : '';

		$js_slog_url = 'tools.php?page=go-slog-show' . preg_replace( '#&limit=[0-9]+|&week=(curr_week|prev_week)#', '', $this->current_slog_vars );

		require_once __DIR__ . '/class-go-slog-admin-table.php';

		$go_slog_table = new GO_Slog_Admin_Table();

		$go_slog_table->log_query = $log_query;
		?>
		<div class="wrap view-slog">
			<?php screen_icon('tools'); ?>
			<h2>
				View Slog
				<select name='go_slog_week' class='select' id="go_slog_week">
					<?php echo go_slog_admin()->build_options( array( 'curr_week' => 'Current Week', 'prev_week' => 'Previous Week' ), $this->week ); ?>
				</select>
			</h2>
			<?php
			if ( isset( $_GET['slog-cleared'] ) )
			{
				?>
				<div id="message" class="updated">
					<p>Slog cleared!</p>
				</div>
				<?php
			}

			$go_slog_table->prepare_items();
			$go_slog_table->custom_display();
			?>
			<input type="hidden" name="js_slog_url" value="<?php echo esc_attr( $js_slog_url ); ?>" id="js_slog_url" />
		</div>
		<?php
	} // end show_log

	/**
	 * Export current log results to a CSV file
	 */
	public function export_csv( $log_query )
	{
		if (
			   ! current_user_can( 'manage_options' )
			|| ! isset( $_REQUEST['_wpnonce'] )
			|| ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'go_slog_csv' )
		)
		{
			wp_die( 'Not cool', 'Unauthorized access', array( 'response' => 401 ) );
		} // end if

		$log_query = $this->log_query();

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename=' . GO_Slog::$config['aws_sdb_domain'] . '.csv' );

		$csv = fopen( 'php://output', 'w' );

		$columns = array(
			'Date',
			'Host',
			'Code',
			'Message',
			'Data',
		);

		fputcsv( $csv, $columns );

		foreach ( $log_query as $key => $row )
		{
			$microtime = explode( '.', $row['log_date'] );

			$line = array(
				date( 'Y-m-d H:i:s', $microtime[0] ) . '.' . $microtime[1],
				$row['host'],
				$row['code'],
				$row['message'],
				$this->format_data( $row['data'] ),
			);

			fputcsv( $csv, $line );
		} // end foreach

		die;
	} // END export_csv

	/**
	 * Returns relevant log items from the log
	 */
	public function log_query()
	{
		GO_Slog::check_domains();

		$this->limit = isset( $_GET['limit'] ) && isset( $this->limits[ $_GET['limit'] ] ) ? $_GET['limit'] : $this->limit;
		$this->week  = isset( $_GET['week'] ) && isset( $this->domain_suffix[ $_GET['week'] ] ) ? $_GET['week'] : $this->week;
		$next_token  = isset( $_GET['next'] ) ? base64_decode( $_GET['next'] ) : NULL;

		return GO_Slog::simple_db()->select(
			'SELECT * FROM `' . GO_Slog::$config['aws_sdb_domain'] . $this->domain_suffix[ $this->week ]
			. '` WHERE log_date IS NOT NULL ' . $this->search_limits()
			. 'ORDER BY log_date DESC LIMIT ' . $this->limit,
			$next_token
		);
	} // END log_query

	/**
	 * Return SQL limits for the three search/filter fields
	 */
	public function search_limits()
	{
		$limits = '';

		if ( isset( $_REQUEST['host'] ) && '' != $_REQUEST['host'] )
		{
			$limits .= " AND host = '" . esc_sql( $_REQUEST['host'] ) . "'";
		} // END if

		if ( isset( $_REQUEST['code'] ) && '' != $_REQUEST['code'] )
		{
			$limits .= " AND code = '" . esc_sql( $_REQUEST['code'] ) . "'";
		} // END if

		if ( isset( $_REQUEST['message'] ) && '' != $_REQUEST['message'] )
		{
			$message = isset( $_GET['message'] ) ? base64_decode( $_GET['message'] ) : $_POST['message'];
			$limits .= " AND message LIKE '%" . esc_sql( $message ) . "%'";
		} // END if

		return $limits;
	} // END search_limits

	/**
	 * Helper function to build select options
	 */
	public function build_options( $options, $existing )
	{
		$select_options = '';

		foreach ( $options as $option => $text )
		{
			$select_options .= '<option value="' . esc_attr( $option ) . '"' . selected( $option, $existing, FALSE ) . '>' . $text . "</option>\n";
		} // END foreach

		return $select_options;
	} // END build_options

	/**
	 * Register the cleaning cron and preemptively run clean domains function
	 */
	public function cron_register_admin_ajax()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			wp_die( 'Not cool', 'Unauthorized access', array( 'response' => 401 ) );
		} // end if

		go_slog()->clean_domains();
		go_slog()->cron_register();
		echo TRUE;
		die;
	} // END cron_register_admin_ajax
}// end GO_Slog_Admin

function go_slog_admin()
{
	global $go_slog_admin;

	if ( ! isset( $go_slog_admin ) )
	{
		$go_slog_admin = new GO_Slog_Admin();
	}// end if

	return $go_slog_admin;
}// end go_slog_admin