<?php
/**
 * Plugin name: Gigaom Slog
 * Description:	A simple way to log events, exceptions, anyting to Loggly and view them within WordPress.
 * Version:     2.1
 * Author: 		Gigaom
 * Author URI: 	http://gigaom.com/
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

require_once __DIR__ . '/components/class-go-slog.php';
go_slog();
