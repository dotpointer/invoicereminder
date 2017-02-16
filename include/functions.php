<?php
	session_start();
	# changelog
	# 2017-02-14 17:57:26

	/*
	CREATE TABLE invoicenagger_debtors(
		id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT NOT NULL,
		invoicenumber INT NOT NULL,
		amount FLOAT NOT NULL,
		collectioncost FLOAT NOT NULL DEFAULT 0,
		remindercost FLOAT NOT NULL DEFAULT 0,
		percentage FLOAT NOT NULL,
		email TINYTEXT NOT NULL,
		email_bcc TINYTEXT NOT NULL,
		invoicedate DATE NOT NULL,
		duedate DATE NOT NULL,
		status INT NOT NULL DEFAULT 0,
		mails_sent INT NOT NULL,
		last_reminder DATETIME NOT NULL,
		reminder_days INT NOT NULL DEFAULT 30,
		template TINYTEXT NOT NULL,
		created DATETIME NOT NULL,
		updated DATETIME NOT NULL
	);

	CREATE TABLE invoicenagger_log(
	id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	message TEXT NOT NULL,
	type INT NOT NULL,
	created DATETIME NOT NULL
	);

	*/

	define('SITE_SHORTNAME', 'invoicenagger');
	define('DATABASE_NAME', 'invoicenagger');

	define('LOG_TYPE_ERROR', -1);
	define('LOG_TYPE_MAIL_SENT', 1);

	define('DEBTOR_STATUS_ERROR', -1);
	define('DEBTOR_STATUS_INACTIVE', 0);
	define('DEBTOR_STATUS_ACTIVE', 1);

	require_once('config.php');
	require_once('base3.php');

	$link = db_connect();

	if (!function_exists('shutdown_function')) {
		# a function to run when the script shutdown
		function shutdown_function($link) {
			if ($link) {
				db_close($link);
			}
		}
	}

	# register a shutdown function
	register_shutdown_function('shutdown_function', $link);

	define('TEMPLATE_DIR', 'templates/');
	define('TEMPLATE_DEFAULT', 'default');
?>
