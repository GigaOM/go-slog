<div class="wrap view-slog">
	<h2>
		View Slog From
		<select name="slog_interval" class="select" id="slog-interval">
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
				$this->request['interval']
			);
			?>
		</select>
		- Now
	</h2>
	<div class="slog-results">
		<div class="slog-search-form">
			<input type="text" id="slog-terms" name="slog_terms" value="<?php echo esc_attr( $this->request['terms'] ); ?>" />
			<select name="slog_column" class="select" id="slog-column">
				<?php
				echo $this->build_options(
					array(
						'domain'   => 'Domain *',
						'code'     => 'Code',
						'message'  => 'Message',
						'class'    => 'Class *',
						'function' => 'Function *',
					),
					$this->request['column']
				);
				?>
			</select>
			<input type="button" class="primary button" id="slog-search-button" name="slog_search_button" value="Search Log Items" /><br />
			<em>* Results will require an exact match.</em><br />
		</div>
		<?php
		$go_slog_table->prepare_items();
		$go_slog_table->custom_display();
		?>
	</div>
</div>