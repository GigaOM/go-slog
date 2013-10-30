<?php

class GO_Slog_Admin_Table extends WP_List_Table
{
	public $log_query = array();

	public function __construct()
	{
		//Set parent defaults
		parent::__construct(
			array(
				'singular' => 'slog-item',  //singular name of the listed records
				'plural'   => 'slog-items', //plural name of the listed records
				'ajax'     => FALSE,        //does this table support ajax?
			)
		);
	} // END __construct

	public function column_default( $item, $column_name )
	{
		switch( $column_name )
		{
			case array_key_exists( $column_name, $this->_column_headers[0] ):
				return $item[ $column_name ];
			default:
				return print_r( $item, TRUE ); // Show the whole array for troubleshooting purposes
		} // END switch
	} // END column_default

	public function column_slog_data( $item )
	{
		return '<pre>' . $item['slog_data'] . '</pre>';
	} // END column_children

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
	} // END get_columns

	public function single_row( $item )
	{
		static $row_class = '';
		$row_class = ( '' == $row_class ) ? ' class="alternate"' : '';

		echo '<tr' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	} // END single_row

	public function display_tablenav( $which )
	{
		if ( 'top' == $which )
		{
			$this->table_nav_top();
		} // END if
		else
		{
			$this->table_nav_bottom();
		} // END else
	} // END display_tablenav

	public function table_nav_top()
	{
		$clear_slog_url   = wp_nonce_url( admin_url( 'admin-ajax.php?action=go-slog-clear' ), 'go_slog_clear' );
		$next_token       = isset( $_GET['next'] ) ? '&next=' . $_GET['next'] : '';
		$csv_export_url   = wp_nonce_url( 'admin-ajax.php?action=go-slog-csv' . go_slog_admin()->current_slog_vars . '&csv=yes' . $next_token, 'go_slog_csv' );

		$count = count( $this->items );
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>" style="min-height: 43px;">
			<div class="alignleft">
				<p>
					<select name='go_slog_limit' class='select' id="go_slog_limit">
						<?php echo go_slog_admin()->build_options( go_slog_admin()->limits, go_slog_admin()->limit ); ?>
					</select>
					Log Items
				</p>
			</div>
			<div class="alignright">
				<?php
				if ( 0 != $count )
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
	} // END table_nav_top

	public function table_nav_bottom()
	{
		if ( '' != Go_Slog::simple_db()->NextToken )
		{
			$next_link = 'tools.php?page=go-slog-show' . go_slog_admin()->current_slog_vars . '&next=' . base64_encode( Go_Slog::simple_db()->NextToken );
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
		} // end if
	} // END table_nav_bottom

	public function prepare_items()
	{
		global $wpdb;

		// Set columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->compile_posts();
	} // END prepare_items

	public function custom_display()
	{		
		if ( ! empty( $this->items ) )
		{
			$this->display();
		} // END if
		else
		{
			?>
			<div id="message" class="error">
				<p>Your Slog is empty.</p>
			</div>
			<?php
		} // END if
	} // END custom_display

	public function compile_posts( $children, $embeds )
	{
		$compiled = array();

		foreach ( $this->log_query as $key => $row )
		{
			$microtime = explode( '.', $row['log_date'] );

			$compiled[] = array(
				'slog_date'    => date( 'Y-m-d H:i:s', $microtime[0] ) . '.' . $microtime[1],
				'slog_host'    => $row['host'],
				'slog_code'    => $row['code'],
			    'slog_message' => $row['message'],
				'slog_data'    => go_slog_admin()->format_data( $row['data'] ),
			);
		} // end foreach

		return $compiled;
	} // END compile_posts
} // END GO_Slog_Admin_Table