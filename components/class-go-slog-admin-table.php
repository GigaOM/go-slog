<?php

class GO_Slog_Admin_Table extends WP_List_Table
{
	public $log_query = NULL;
	public $limit_options = NULL;
	public $request = array();

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
	 * Initial prep for WP_List_Table
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items()
	{
		// Number of items in the results
		$total_items = $this->log_query->count(); //return the total number of affected rows

		// Number of items displayed per page
		$per_page = isset( $this->request['limit'] ) ? $this->request['limit'] : 50;

		// The pagination links are automatically built according to these parameters
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		// Set columns
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Compile results into something suitable for the table
		$this->items = $this->compile_posts();
	} //end prepare_items

	/**
	 * Display the log or an error message that the log is empty
	 */
	public function custom_display()
	{
		$this->display();
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
		$next_page  = isset( $_GET['paged'] ) ? $_GET['paged'] : NULL;

		if ( $next_page )
		{
			$this->log_query->next();
		}

		foreach ( $this->log_query->get_objects() as $key => $value )
		{
			// The from column is actually made up of multiple values that we may or may not have depeneding on the age of the log item
			$from = '';

			if ( isset( $value->event->json->class ) && $value->event->json->function )
			{
				$from = $value->event->json->class . ':' . $value->event->json->function . '() - ';
			} // END if

			$from .= $value->event->json->from;

			$compiled[] = array(
				'slog_date'    => date( 'M j, H:i:s', $value->timestamp / 1000 ), // shave off milliseconds
				'slog_domain'  => isset( $value->event->json->domain ) ? esc_html( $value->event->json->domain ) : 'unknown',
				'slog_code'    => esc_html( $value->event->json->code ),
				'slog_from'    => esc_html( $from ),
				'slog_message' => esc_html( $value->event->json->message ),
				'slog_data'    => esc_html( print_r( unserialize( $value->event->json->data ), TRUE ) ),
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
			'slog_date'    => 'Date (PST)',
			'slog_domain'  => 'Domain',
			'slog_code'    => 'Code',
			'slog_message' => 'Message',
			'slog_from'    => 'From',
			'slog_data'    => 'Data',
		);

		return $columns;
	} //end get_columns

	/**
	 * Returns a message when there's no items to display
	 */
	public function no_items()
	{
		if ( isset( $this->request['terms'] ) && '' != $this->request['terms'] )
		{
			?>
			No log items were found that matched these terms.
			<?php
		} // END if
		else
		{
			?>
			This log is empty.
			<?php
		} // END else
	} // END no_items

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
		if ( 'top' == $which && $this->limit_options )
		{
			?>
			Show
			<select name="slog_limit" class="select" id="slog-limit">
				<?php echo wp_kses( $this->limit_options, array( 'option' => array( 'value' => array(), 'selected' => array() ) ) ); ?>
			</select>
			items per page.
			<?php
		}
	} //end extra_tablenav
}
//end GO_Slog_Admin_Table