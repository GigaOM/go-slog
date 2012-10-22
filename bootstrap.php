<?php

if ($config = Go_Wp_Config::load('go-slog'))
{
	require_once dirname(__FILE__).'/go-slog.php';

	new Go_Slog($config['aws_access_key'], $config['aws_secret_key'], $config['aws_sdb_domain']);
}