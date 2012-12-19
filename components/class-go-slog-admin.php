<?php

class Go_Slog_Admin
{
	public $var_dump = FALSE;
	public $limit    = 1000;

	/**
	 * Constructor to establish a couple ajax endpoints
	 */
	public function __construct()
	{
		add_action( 'wp_ajax_go-slog-show', array( $this, 'show_log' ) );
		add_action( 'wp_ajax_go-slog-clear', array( $this, 'clear_log' ) );
	} // end __construct

	/**
	 * Delete all entries in the log
	 */
	public function clear_log()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			return;
		} // end if

		Go_Slog::simple_db()->deleteDomain( Go_Slog::$config['aws_sdb_domain'] );

		die( '<p><strong>Log Cleared!</strong></p>' );
	} // end clear_log

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

		$this->var_dump = ( $_GET['var_dump'] == 'yes' ) ? TRUE : FALSE;
		$next_token     = ( $_GET['next'] != '' ) ? base64_decode( $_GET['next'] ) : NULL;

		$log_query = Go_Slog::simple_db()->select( 'SELECT * FROM `' . Go_Slog::$config['aws_sdb_domain'] . '` WHERE log_date IS NOT NULL ORDER BY log_date DESC LIMIT ' . $this->limit, $next_token );

		if ( $_GET['csv'] == 'yes' )
		{
			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment;filename=' . Go_Slog::$config['aws_sdb_domain'] . '.csv' );
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

				fputcsv($csv, $line);
			} // end foreach

			exit;
		} // end if

		?>
		<style type="text/css" media="screen">
		table {
		  width: 100%;
		  border: 1px solid black;
		}

		th, td {
			padding: 3px 7px;
		}

		th {
			text-align: left;
			border-bottom: 4px solid black;
		}

		th span {
			float: right;
		}

		td {
			border-bottom: 1px solid silver;
		}

		tr.odd td {
			background-color: #efefef;
		}
		</style>
		<table border="0" cellspacing="0" cellpadding="0">
		<tr><th class="date">Date</th><th>Host</th><th>Code</th><th>Message</th><th>Data <span><a href="admin-ajax.php?action=go-slog-clear" title="Clear Log">Clear Log</a></span></th></tr>
		<?php

		foreach ($log_query as $key => $row)
		{
			$class = ( $key & 1 ) ? 'odd' : 'even';

			$microtime = explode( '.', $row['log_date'] );

			echo '<tr valign="top" class="' . $class . '">';

			echo '<td>' . date( 'Y-m-d H:i:s', $microtime[0] ) . '.' . $microtime[1] . '</td>';
			echo '<td>' . $row['host'] . '</td>';
			echo '<td>' . $row['code'] . '</td>';
			echo '<td>' . $row['message'] . '</td>';
			echo '<td><pre>' . $this->format_data( $row['data'] ) . '</pre></td>';

			echo "</tr>\n";
		} // end foreach

		echo '</table>';


		if ( Go_Slog::simple_db()->NextToken != '' )
		{
			$var_dump = ($this->var_dump) ? '&amp;var_dump=yes' : '';
			echo '<p><a href="admin-ajax.php?action=go-slog-show&amp;next=' . base64_encode( Go_Slog::simple_db()->NextToken ) . $var_dump . '" title="Next Page">Next Page</a></p>';
		} // end if

		die();
	} // end show_log
}// end class
