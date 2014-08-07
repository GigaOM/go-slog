<?php

class GO_Slog_Admin_Table extends WP_List_Table
{
	public $log_query = NULL;

	public function __construct()
	{
		// Set parent defaults
		parent::__construct(
			array(
				'singular' => 'loggly-item',  //singular name of the listed records
				'plural'   => 'loggly-items', //plural name of the listed records
				'ajax'     => FALSE,          //does this table support ajax?
			)
		);
	} //end __construct

	/**
	 * Initial prep for WP_List_Table
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items()
	{
		//
		//Pagination parameters
		//
		//Number of elements in your table?
		$total_items = $this->log_query->count(); //return the total number of affected rows

		//How many to display per page?
		$per_page = 50;

		//How many pages do we have in total? NOTE: pager's page_count 0-indexed
		//$this->log_query->page_count() == 0 ? $total_pages = 1 : $total_pages = $this->log_query->page_count();

		$current_page = $this->get_pagenum();

		//
		//Register the pagination
		//
		//The pagination links are automatically built according to those parameters
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );

		// Set columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// fetch items
		$data = $this->compile_posts();

		$this->items = $data;
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
				<p>Your log is empty.</p>
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
		$compiled = array();

		// get current page from WP_List_Table's pagination url vars, to assist with directing result paging
		$next_page  = isset( $_GET['paged'] ) ? base64_decode( $_GET['paged'] ) : NULL;
		if ( $next_page )
		{
			$this->log_query->next();
		}

		foreach ( $this->log_query->get_objects() as $key => $value )
		{
			$compiled[] = array(
				'loggly_id'       => esc_html( $value->id ),
				'loggly_date'     => date( 'Y-m-d H:i:s', $value->timestamp / 1000 ), // shave off millis
				'loggly_class'    => esc_html( $value->tags[2] ),
				'loggly_method'   => esc_html( $value->tags[1] ),
				'loggly_location' => esc_html( $value->event->json->from ),
				'loggly_message'  => esc_html( $value->event->json->message ),
				'loggly_data'     => esc_html( print_r( unserialize( $value->event->json->data ), TRUE ) ),
			);
		} //end foreach

		return $compiled;
	} //end compile_posts


	/**
	 * Return an array of the columns with keys that match the compiled items
	 *
	 * @return array $columns an associative array of columns
	 */
	public function get_columns()
	{
		$columns = array(
			'loggly_id'       => 'Loggly ID',
			'loggly_date'     => 'Date (PST)',
			'loggly_class'    => 'Class',
			'loggly_method'   => 'Method',
			'loggly_location' => 'File',
			'loggly_message'  => 'Message',
			'loggly_data'     => 'Data',
		);

		return $columns;
	} //end get_columns

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
	 * Display the individual rows of the table
	 *
	 * @param array $item an array of search controls
	 */
	public function single_row( $item )
	{
		static $row_class = '';
		$row_class = ( '' == $row_class ) ? ' class="alternate"' : '';

		echo '<tr' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	} //end single_row

	/**
	 * Display nav items for the table
	 *
	 * @param string $which "top" to display the nav, else "bottom"
	 */
	public function extra_tablenav( $which )
	{
		if ( 'top' == $which )
		{
			$this->table_nav_top( $which );
		}
		else
		{
			$this->table_nav_bottom();
		} //end else
	} //end extra_tablenav

	/**
	 * Display nav items for above the table
	 * 
	 * @param string $which "top" to display the nav, else "bottom"
	 */
	public function table_nav_top( $which )
	{
			$count = count( $this->items );
			$total_count = $this->log_query->count();
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>" style="min-height: 43px;">
				<div class="alignleft">
					<p>
						<?php echo $count; ?> Log Items / <?php echo $total_count; ?> Total
					</p>
				</div>
				<div class="alignright">
					<?php
					if ( 1 < $count )
					{
						?>
						<!-- preserve this space for something, possibly also export and clear -->
					<?php
					} // END if
					?>
				</div>
				<br class="clear"/>
			</div>
		<?php
	} //end table_nav_top

	/**
	 * Display nav items to show below the table.
	 */
	public function table_nav_bottom()
	{
			?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="pagination-links">
					</span>
				</div>
			</div>
			<?php
	} //end table_nav_bottom
}
//end GO_Slog_Admin_Table