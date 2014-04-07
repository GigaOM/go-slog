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

Why Does This Exist?
--------------------

We originally created this to allow us to log errors in our code when we weren't able to access the error logs on the server we saw the errors and weren't able to reproduce them locally.  However, since then we've found it useful in a few other cases as well.

Here's our short list (you may be able to think of more):

1. When you need to log errors but don't have admin access to the error logs on the server.
2. When you need a cosolidated log between two different servers that are running related code.
	* In our case we had two servers talking to each other and needed a view into how that conversation was going in a consolidated fashion.
3. When you have a rare error that you need to log it but don't want to dig through months of error log files.

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
		* Note: we currently have no method for doing an exponentional backoff as [suggested by Amazon](http://docs.aws.amazon.com/AmazonSimpleDB/latest/DeveloperGuide/APIUsage.html#APIErrorRetries) so too many attempts to write to the slog in a short amount of time can trigger ServiceUnavailable responses from Amazon SDB. In our experience this hasn't been much of a problem but it's on our to do list of things to improve.
3. View Slog: /wp-admin/tools.php?page=go-slog-show
	* Slogs can be paged through with up to 1000 items at a time (SDB has a 1000 item query limit)
	* Slog items can also be exported to CSV.
	* If you need to clear out your Slog you can do that from the admin panel as well.

Report Issues, Contribute Code, or Fix Stuff
--------------------------------------------

https://github.com/GigaOM/go-slog/issues
