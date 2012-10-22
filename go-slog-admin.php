<?php

class Go_Slog_Admin
{	
	var $var_dump = FALSE;
	var $limit    = 1000;
	
	public function __construct()
	{		
		add_action('wp_ajax_go-slog-show', array($this, 'show_log'));
		add_action('wp_ajax_go-slog-clear', array($this, 'clear_log'));
	}
	// END __construct
	
	function show_log()
	{				
		if (!current_user_can( 'manage_options' ))
			return;
		
		nocache_headers();
		
		Go_Slog::check_domain();

		$simple_db = go_get_simple_db( Go_Slog::$aws_access_key, Go_Slog::$aws_secret_key );
				
		$this->var_dump = ($_GET['var_dump'] == 'yes') ? TRUE : FALSE; 
		$next_token     = ($_GET['next'] != '') ? base64_decode($_GET['next']) : NULL;
		
		$log_query = $simple_db->select('SELECT * FROM `'.Go_Slog::$aws_sdb_domain.'` WHERE log_date IS NOT NULL ORDER BY log_date DESC LIMIT '.$this->limit, $next_token);

		if( $_GET['csv'] == 'yes' )
		{
            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment;filename='.Go_Slog::$aws_sdb_domain.'.csv' );
            $csv = fopen('php://output', 'w');
			
			$columns = array(
				'Date',
				'Host',
				'Code',
				'Message',
				'Data'
			);
			
			fputcsv($csv, $columns);
			
			foreach ($log_query as $key => $row)
			{						
				$microtime = explode( '.', $row['log_date'] );
						
				$line = array(
					date( 'Y-m-d H:i:s', $microtime[0] ).'.'.$microtime[1],
					$row['host'],
					$row['code'],
				    $row['message'],
					$this->format_data($row['data']),
				);
				
				fputcsv($csv, $line);
			}
						
			exit;
		}
		
print <<<CODE
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
CODE;
			
		foreach ($log_query as $key => $row)
		{
			$class     = ($key & 1) ? 'odd' : 'even';
						
			$microtime = explode( '.', $row['log_date'] );
									
			print '<tr valign="top" class="'.$class.'">';
			
			print '<td>'.date( 'Y-m-d H:i:s', $microtime[0] ).'.'.$microtime[1].'</td>';
			print '<td>'.$row['host'].'</td>';
			print '<td>'.$row['code'].'</td>';
			print '<td>'.$row['message'].'</td>';
			print '<td><pre>'.$this->format_data($row['data']).'</pre></td>';
			
			print '</tr>'."\n";
		}	
			
print <<<CODE
</table>
CODE;
		
		if ($simple_db->NextToken != '')
		{
			$var_dump = ($this->var_dump) ? '&var_dump=yes' : '';
			print '<p><a href="admin-ajax.php?action=go-slog-show&next='.base64_encode($simple_db->NextToken).$var_dump.'" title="Next Page">Next Page</a></p>';
		}
		
		die();
	}
	// END show_log
	
	function format_data($data)
	{
		if ($this->var_dump == FALSE)
		{
			$data = print_r(unserialize($data), TRUE);
		}
		else 
		{
			ob_start(); 
			var_dump(unserialize($data));
			$data = ob_get_clean();
		}
		
		return $data;
	}
	// END format_data
	
	function clear_log()
	{
		if (!current_user_can( 'manage_options' ))
			return;
		
		$simple_db = go_get_simple_db( Go_Slog::$aws_access_key, Go_Slog::$aws_secret_key );
		
		$simple_db->deleteDomain( Go_Slog::$aws_sdb_domain );
		
		die('<p><strong>Log Cleared!</strong></p>');
	}
	// END clear_log
}
// END Go_Slog_Admin

new Go_Slog_Admin;