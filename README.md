Gigaom Slog
===========

* Tags: wordpress, amazon simple db, logging
* Requires at least: 3.6.1
* Tested up to: 3.6.1
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Description
-----------

A way to log occurances to an Amazon Simple DB (requires [Gigaom Simple DB](http://github.com/GigaOM/go-simple-db/)).

Usage Notes
-----------

1. Set config info by filtering the go_config hook and returning an array of Amazon Simple DB credentials when the second filter attribute is go-slog.
	* ```array(
		'aws_access_key' => 'YOUR_KEY',
		'aws_secret_key' => 'YOUR_SECRET',
		'aws_sdb_domain' => 'YOUR_SDB_DOMAIN',
	);```
	* The SDB Domain value is analagous to an SQL Table. It's the space where your log items will be written to.  If the SDB Domain doesn't exist yet [Gigaom Simple DB](http://github.com/GigaOM/go-simple-db/) will create it for you.
	* See: [Amazon Simple DB Getting Started Guide](http://docs.aws.amazon.com/AmazonSimpleDB/latest/GettingStartedGuide/Welcome.html) for more information on getting your Key and Secret values.
2. Log items: ```apply_filters( 'go_slog', $code, $message, $data );```
	* $code - Some error code string (e.g. warning, error, error-type-1, etc...)
	* $message - Some error message (e.g. Attempt to contact the endpoint failed.)
	* $data - An array of data that will be helpful in debugging (e.g. ```array( 'post_id' => 131, 'post_title' => 'Test Post' )```)
		* Note: Amazon Simple DB values have a [1024 byte size limit](http://docs.aws.amazon.com/AmazonSimpleDB/latest/DeveloperGuide/SDBLimits.html).
3. View Log: /wp-admin/tools.php?page=go-slog-show

Report Issues, Contribute Code, or Fix Stuff
--------------------------------------------

https://github.com/GigaOM/go-slog/issues
