<?php

class GO_Slog_Admin_Table extends WP_List_Table
{
	public $log_query = array();

	public function __construct()
	{
		// Set parent defaults
		parent::__construct(
			array(
				'singular' => 'slog-item',  //singular name of the listed records
				'plural'   => 'slog-items', //plural name of the listed records
				'ajax'     => FALSE,        //does this table support ajax?
			)
		);
	} //end __construct

	/**
	 * Display the various columns for each item, it first checks
	 * for a column-specific method. If none exists, it defaults to this method instead.
	 *
	 * @param array $item This is used to store the raw data you want to display.
	 * @param string $column_name a slug of a column name
	 * @return string $items an item index at the $column_name
	 */
	public function column_default( $item, $column_name )
	{
		if ( array_key_exists( $column_name, $this->_column_headers[0] ) )
		{
			return $item[ $column_name ];
		} //end if
	} //end column_default

	/**
	 * Custom display stuff for the data column
	 *
	 * @param array $item this is used to store the slog data you want to display.
	 * @return String an index of the array $item
	 */
	public function column_slog_data( $item )
	{
		return '<pre>' . $item['slog_data'] . '</pre>';
	} //end column_slog_data

	/**
	 * Return an array of the columns with keys that match the compiled items
	 *
	 * @return array $columns an associative array of columns
	 */
	public function get_columns()
	{
		$columns = array(
			'slog_date'    => 'Date',
			'slog_host'    => 'Host',
			'slog_code'    => 'Code',
			'slog_message' => 'Message',
			'slog_data'    => 'Data',
		);

		return $columns;
	} //end get_columns

	/**
	 * Display the individual rows of the table
	 *
	 * @param array $item an array of search controls
	 */
	public function single_row( $item )
	{
		static $row_class = '';
		$row_class = ( '' == $row_class ) ? ' class="alternate"' : '';

		if ( isset( $item['search'] ) )
		{
			$host    = isset( $_REQUEST['host'] ) ? $_REQUEST['host'] : '';
			$code    = isset( $_REQUEST['code'] ) ? $_REQUEST['code'] : '';
			// Handle the two cases of a message value seperately
			$message = isset( $_POST['message'] ) ? $_POST['message'] : '';
			$message = ! isset( $_POST['message'] ) && isset( $_GET['message'] ) ? base64_decode( $_GET['message'] ) : '';
			?>
			<tr class="search-controls">
				<form action="tools.php?page=go-slog-show&search=yes<?php echo go_slog()->admin->current_slog_vars; ?>" method="post">
					<td></td>
					<td>
						<p>
							<span>*</span><input type="text" name="host" value="<?php echo esc_attr( $host ); ?>" />
						</p>
					</td>
					<td>
						<p>
							<span>*</span><input type="text" name="code" value="<?php echo esc_attr( $code ); ?>" />
						</p>
					</td>
					<td>
						<p>
							<input type="text" name="message" value="<?php echo esc_attr( $message ); ?>" />
						</p>
					</td>
					<td>
						<p><button class="button">Search</button></p>
					</td>
				</form>
			</tr>
			<tr class="search-instructions">
				<td></td>
				<td colspan="4">* Results will only be returned if the column value matches exactly.</td>
			</tr>
			<?php
			if ( 1 == count( $this->items ) )
			{
				?>
				<tr class="no-items">
					<td colspan="5">No log items found.</td>
				</tr>
				<?php
			} //end if
		} //end if
		else
		{
			echo '<tr' . $row_class . '>';
			echo $this->single_row_columns( $item );
			echo '</tr>';
		} //end else
	} //end single_row

	/**
	 * Display nav items for the table
	 *
	 * @param string $which top to display the nav, else bottom
	 */
	public function display_tablenav( $which )
	{
		if ( 'top' == $which )
		{
			$this->table_nav_top();
		}
		else
		{
			$this->table_nav_bottom();
		} //end else
	} //end display_tablenav

	/**
	 * Display nav items for above the table
	 */
	public function table_nav_top()
	{
		$clear_slog_url   = wp_nonce_url( admin_url( 'admin-ajax.php?action=go-slog-clear&week=' . go_slog()->admin->week ), 'go_slog_clear' );
		$next_token       = isset( $_GET['next'] ) ? '&next=' . $_GET['next'] : '';
		$csv_export_url   = wp_nonce_url( 'admin-ajax.php?action=go-slog-csv' . go_slog()->admin->current_slog_vars . '&csv=yes' . $next_token, 'go_slog_csv' );

		$count = count( $this->items );
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>" style="min-height: 43px;">
			<div class="alignleft">
				<p>
					<select name='go_slog_limit' class='select' id="go_slog_limit">
						<?php echo go_slog()->admin->build_options( go_slog()->admin->limits, go_slog()->admin->limit ); ?>
					</select>
					Log Items
				</p>
			</div>
			<div class="alignright">
				<?php
				if ( 1 < $count )
				{
					?>
					<p>
						<a href="<?php echo $csv_export_url; ?>" title="Export CSV" class="button export-csv">
							Export CSV
						</a>
						<a href="<?php echo $clear_slog_url; ?>" title="Clear Slog" class="button clear-slog">
							Clear Slog
						</a>
					</p>
					<?php
				} // END if
				?>
			</div>
			<br class="clear" />
		</div>
		<?php
	} //end table_nav_top

	/**
	 * Display nav items to show below the table.
	 */
	public function table_nav_bottom()
	{
		if ( '' != Go_Slog::simple_db()->NextToken )
		{
			$next_link = 'tools.php?page=go-slog-show' . go_slog()->admin->current_slog_vars . '&next=' . base64_encode( Go_Slog::simple_db()->NextToken );
			?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="pagination-links">
						<a class="next-page" href="<?php echo $next_link; ?>">
							Next Page &rsaquo;
						</a>
					</span>
				</div>
			</div>
			<?php
		} //end if
	} //end table_nav_bottom

	/**
	 * Initial prep for WP_List_Table
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items()
	{
		global $wpdb;

		// Set columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->compile_posts();
	} //end prepare_items

	/**
	 * Display the log or an error message that the log is empty
	 */
	public function custom_display()
	{
		if ( ! empty( $this->items ) )
		{
			$this->display();
		} //end if
		else
		{
			?>
			<div id="message" class="error">
				<p>Your Slog is empty.</p>
			</div>
			<?php
		} //end else
	} //end custom_display

	/**
	 * Compile the log items into a format appropriate for WP_List_Table
	 *
	 * @return array $compiled
	 */
	public function compile_posts()
	{
		$compiled = array( array( 'search' => TRUE ) );

		foreach ( $this->log_query as $key => $row )
		{
			$microtime = explode( '.', $row['log_date'] );

			$compiled[] = array(
				'slog_date'    => date( 'Y-m-d H:i:s', $microtime[0] ) . '.' . $microtime[1],
				'slog_host'    => esc_html( $row['host'] ),
				'slog_code'    => esc_html( $row['code'] ),
				'slog_message' => esc_html( $row['message'] ),
				'slog_data'    => esc_html( go_slog()->admin->format_data( $row['data'] ) ),
			);
		} //end foreach

		return $compiled;
	} //end compile_posts
}
//end GO_Slog_Admin_Table