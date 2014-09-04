=== Gigaom Slog ===
Contributors: methnen, zbtirrell, okredo

Tags: wordpress, Loggly, logging
Requires at least: 3.6.1
Tested up to: 3.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

A way to log occurrences to the Loggly API (requires [Gigaom Loggly](http://github.com/GigaOM/go-loggly/)).

We originally created this to allow us to log errors in our code when we weren't able to access the PHP error logs on the server and weren't able to reproduce them locally.  However, since then we've found it useful in a few other cases as well.

Here's our short list (you may be able to think of more):

1. When you need to log errors but don't have admin access to the error logs on the server.
2. When you need a consolidated log between two different servers that are running related code.
	* In our case we had two servers talking to each other and needed a view into how that conversation was going in a consolidated fashion.
3. When you have a rare error that you need to log but don't want to dig through months of error log files.

== Usage ==

1. All necessary config info is already set up in [Gigaom Loggly](http://github.com/GigaOM/go-loggly/).
	* All log entries are stored on the Loggly account dashboard at https://{account_name}.loggly.com for the duration specified in their service plan.
2. Log items: ```apply_filters( 'go_slog', $code, $message, $data );```
	* $code - Some error code string (e.g. warning, error, error-type-1, etc...)
	* $message - Some error message (e.g. Attempt to contact the endpoint failed.)
	* $data - An array of data that will be helpful in debugging (e.g. ```array( 'post_id' => 131, 'post_title' => 'Test Post' )```)
		* Note: Loggly stores 1Mb per log event uploaded via their API, in batches [limited](https://www.loggly.com/docs/http-bulk-endpoint/) to no larger than 5Mb. 
3. View Slog: /wp-admin/tools.php?page=go-slog-show
	* Slogs can be paged through going back up to a week back. (We are using Loggly's 50 item per item query default resultset size).
	* The advantage of using this view is that it only returns go-slog'd entries sorted into the `code, message, data` columns. Similar filtering, and more fine-grained search is of course readily available on the Loggly account dashboard.

== Installation ==

1. Upload `go-simple-db` and `go-slog` to the `/wp-content/plugins/` directory
2. Activate 'Gigaom Simple DB' and 'Gigaom Slog' through the 'Plugins' menu in WordPress

== Contributing ==

This plugin is developed and [available on GitHub](https://github.com/GigaOM/go-slog/). Contributions and questions are welcome!
