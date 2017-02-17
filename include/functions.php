<?php
	session_start();
	# changelog
	# 2017-02-14 17:57:26 - initial version
	# 2017-02-17 00:54:02 - updating
	# 2017-02-17 01:20:43 - bugfix working dir

	/*
	CREATE TABLE invoicenagger_debtors(
		id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT NOT NULL,
		invoicenumber INT NOT NULL,
		name TINYTEXT NOT NULL,
		address TINYTEXT NOT NULL,
		zipcode TINYTEXT NOT NULL,
		city TINYTEXT NOT NULL,
		orgno TINYTEXT NOT NULL,
		amount FLOAT NOT NULL,
		collectioncost FLOAT NOT NULL DEFAULT 0,
		debtor TEXT NOT NULL,
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
		day_of_month int not null default 0,
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

	define('DEBTOR_STATUS_ACTIVE', 1);
	define('DEBTOR_STATUS_ERROR', -1);
	define('DEBTOR_STATUS_INACTIVE', 0);

	define('LOG_TYPE_ERROR', -1);
	define('LOG_TYPE_MAIL_SENT', 1);

	define('REPLY_TO', 'Your Name <your@email.com>');
	define('FROM', 'Your Name <your@email.com>');

	define('TEMPLATE_DEFAULT', 'default.txt');
	define('TEMPLATE_DIR', 'templates/');

	# verbosity
	define('VERBOSE_OFF', 0);		# no info at all
	define('VERBOSE_ERROR', 1);		# only errors
	define('VERBOSE_INFO', 2);		# above and things that changes
	define('VERBOSE_DEBUG', 3);		# above and verbose info
	define('VERBOSE_DEBUG_DEEP', 4);

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

	/**
	* array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	* keys to arrays rather than overwriting the value in the first array with the duplicate
	* value in the second array, as array_merge does. I.e., with array_merge_recursive,
	* this happens (documented behavior):
	*
	* array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	* => array('key' => array('org value', 'new value'));
	*
	* array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	* Matching keys' values in the second array overwrite those in the first array, as is the
	* case with array_merge, i.e.:
	*
	* array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
	* => array('key' => 'new value');
	*
	* Parameters are passed by reference, though only for performance reasons. They're not
	* altered by this function.
	*
	* @param array $array1
	* @param mixed $array2
	* @author daniel@danielsmedegaardbuus.dk
	* @return array
	*/
	function &array_merge_recursive_distinct(array &$array1, &$array2 = null) {
		$merged = $array1;

		if (is_array($array2)) {
			foreach ($array2 as $key => $val) {
				if (is_array($array2[$key])) {
					$merged[$key] = isset($merged[$key]) && is_array($merged[$key]) ? array_merge_recursive_distinct($merged[$key], $array2[$key]) : $array2[$key];
				} else {
					$merged[$key] = $val;
				}
			}
		}
		return $merged;
	}

	# to disable all debtors with a certain template
	function disable_debtors_with_template($link, $template) {
		# disable this debtor
		$sql = '
			UPDATE
				invoicenagger_debtors
			SET
				status="'.dbres($link,DEBTOR_STATUS_ERROR).'",
				updated="'.dbres($link, date('Y-m-d H:i:s')).'"
			WHERE
				template="'.dbres($link, $template).'"
				';
		$r = db_query($link, $sql);
		if ($r === false) {
			echo db_error($link);
			die(1);
		}
		return true;
	}


	# to disable a debtor
	function set_debtor_status($link, $id, $status) {
		# disable this debtor
		$sql = '
			UPDATE
				invoicenagger_debtors
			SET
					status="'.dbres($link, $status).'",
					updated="'.dbres($link, date('Y-m-d H:i:s')).'"
			WHERE id="'.dbres($link, $id).'"';
		$r = db_query($link, $sql);
		if ($r === false) {
			echo db_error($link);
			die(1);
		}
	}

	# debug printing
	function cl($link, $level, $s) {

		global $config;


		# find out level of verbosity
		switch ($level) {
			default:
			case VERBOSE_ERROR:
				$l = 'E';
				break;
			case VERBOSE_INFO:
				$l = 'I';
				break;
			case VERBOSE_DEBUG:
			case VERBOSE_DEBUG_DEEP:
				$l = 'D';
				break;

		}

		# is verbosity on and level is enough?
		if ($config['main']['verbose'] && $config['main']['verbose'] >= $level) {
			echo date('Y-m-d H:i:s').' '.$l.' '.$s."\n";
		}

		# is loglevel on and level is enough - the try to append to log
		if (
			$config['main']['loglevel'] &&
			$config['main']['loglevel'] >= $level &&
			$link
		) {

			# log the error
			$iu = dbpia($link, array(
				'message' => $s,
				'type' => $level,
				'created' => date('Y-m-d H:i:s')
			));
			$sql = 'INSERT INTO invoicenagger_log ('.implode(',', array_keys($iu)).') VALUES('.implode(',', $iu).')';
			$r = db_query($link, $sql);
			if ($r === false) {
				echo db_error($link);
				die(1);
			}

		}

		return true;
	}

?>
