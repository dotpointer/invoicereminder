<?php
	# changelog
	# 2017-02-14 17:31:52 - initial version
	# 2017-02-17 00:54:23 - updating
	# 2017-02-17 01:20:24 - bugfix working dir
	# 2017-02-17 23:57:27 - adding id_debtors to log
	# 2017-02-25 21:42:42 - setting mail address to global one
	# 2017-05-02 11:08:25 - bugfix, adding reminder cost
	# 2017-05-02 11:13:36 - removing invoice number from required values
	# 2017-12-09 20:53:00 - adding Riksbanken reference rate

	require_once('include/functions.php');

	# change dir to the same as the script
	chdir(dirname(__FILE__));

	$opts = getopt('a:dhv:', array('action:', 'dryrun', 'help', 'verbose:'));

	$action = false;

	# default config
	$config = array(
		'main' => array(
			'verbose' => VERBOSE_ERROR,
			'loglevel' => VERBOSE_INFO,
			'dryrun' => false
		)
	);

	$config_opt = $config;

	# walk parameters
	foreach ($opts as $k => $v) {
		# find out what parameter that is used
		switch ($k) {
			case 'a':
			case 'action':
				$action = $v;
				break;
			case 'd': # only dry-run (do not run command)
			case 'dryrun':
				$config_opt['main']['dryrun'] = true;
				cl($link, VERBOSE_DEBUG, 'Dryrun mode activated');
				break;
			case 'h':
			case 'help':
?>
Invoice reminder application

-a=<action>, --action=<action>
	To run an action
	<action>
		remind
			To check for and send reminders
		errorreset
			To reset all errors on all debtors with status error by setting
			them back to active. (Does not change inactivated debtors)
		remindreset
			To reset all last reminded dates on all active debtors.
		updatereference
			To update reference table for Riksbankens reference rate.
			(Needs to be done after 1 of january and after 1 of july)
-d, --dryrun
	Do not send any mails, for testing purposes.
-v[v,vv,vvv], --verbose[v,vv,vvv]
	To set verbosity
<?php
				die(0);
			case 'v': # be verbose
			case 'verbose':
				# determine and set level of verbosity
				switch ($v) {
					default: # error
						$config_opt['main']['verbose'] = VERBOSE_ERROR;
						break;
					case 'v': # error, info
						$config_opt['main']['verbose'] = VERBOSE_INFO;
						break;
					case 'vv': # error, info, debug
						$config_opt['main']['verbose'] = VERBOSE_DEBUG;
						break;
					case 'vvv': # error, info, debug, debug deep
						$config_opt['main']['verbose'] = VERBOSE_DEBUG_DEEP;
						break;
				}

				break;
		}
		# make sure the config from parameters override all
		$config = array_merge_recursive_distinct($config, $config_opt);
	}

	# walk actions
	switch ($action) {

		case 'errorreset': # to reset all errors

			# log it
			cl(
				$link,
				VERBOSE_INFO,
				'Resetting all errored debtors to active.'
			);

			$sql = '
				UPDATE
					invoicenagger_debtors
				SET
					status="'.dbres($link, DEBTOR_STATUS_ACTIVE).'"
				WHERE
					status="'.dbres($link, DEBTOR_STATUS_ERROR).'"';
			cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
			$r = db_query($link, $sql);
			if ($r === false) {
				cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
				die(1);
			}
			die(0);

		case 'remind':

			# get reference rate, descending
			$sql = '
				SELECT
					*
				FROM
					invoicenagger_riksbank_reference_rate
				ORDER BY updated DESC
				';
			$referencerate = db_query($link, $sql);

			# log it
			cl(
				$link,
				VERBOSE_DEBUG,
				'Searching for debtors to remind'
			);

			# loop while there are debtors
			do {
				# get all active debtors with active status and not reminded yet
				$sql = '
					SELECT
						*
					FROM
						invoicenagger_debtors
					WHERE
						status='.dbres($link, DEBTOR_STATUS_ACTIVE).'
						AND
						last_reminder <= timestampadd(day, -reminder_days,now())
						AND (
							day_of_month = 0
							OR
							DAYOFMONTH(NOW()) >= day_of_month
						)
					LIMIT 1;
					';

				cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
				$debtor = db_query($link, $sql);
				if ($debtor === false) {
					cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
					die(1);
				}

				# no debtor found?
				if (!count($debtor) || !isset($debtor[0])) {

					# log it
					cl($link, VERBOSE_DEBUG, 'No more debtors found');

					# get out
					break;
				}

				# simplify debtor
				$debtor = reset($debtor);

				# get the template
				$templatefile = TEMPLATE_DIR.$debtor['template'];

				# log it
				cl($link, VERBOSE_DEBUG, 'Using template: '.$templatefile, $debtor['id']);


				# no template file, fatal error
				if (!$templatefile) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# log it
					cl(
						$link,
						VERBOSE_ERROR,
						TEMPLATE_DIR.
							$debtor['template'].
							' does not exist',
						$debtor['id']
					);

					# take next debtor
					continue;
				}

				# get template file
				$template = file_get_contents($templatefile);

				# failed reading template file
				if ($template === false) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# log it
					cl(
						$link,
						VERBOSE_ERROR,
						TEMPLATE_DIR.
							$debtor['template'].
							' is not readable',
						$debtor['id']
					);

					# take next debtor
					continue;
				}

				$template = trim($template);

				# failed reading template file
				if (!strlen($template)) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# log it
					cl(
						$link,
						VERBOSE_ERROR,
						TEMPLATE_DIR.
							$debtor['template'].
							' is empty',
						$debtor['id']
					);

					# take next debtor
					continue;
				}

				# extract subject
				if (strpos($template, '---') === false) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# log it
					cl(
						$link,
						VERBOSE_ERROR,
						TEMPLATE_DIR.$debtor['template'].
							' is missing subject/body divider ---',
						$debtor['id']
					);

					# take next debtor
					continue;
				}


				# start date and end date for interest calculation
				$date1 = new DateTime($debtor['duedate']);
				$date2 = new DateTime();

				# no need to remove one day here, as PHP does not add one
				$days_elapsed = $date2->diff($date1)->format("%a");

				# calculate interest for one day
				$perday = (($debtor['amount']) * $debtor['percentage']) / 365;

				# calculate interest for all days that has elapsed
				# $interest = $perday * $days_elapsed;

				# calculate interest for all days that has elapsed
				$interest = 0;
				for ($i=1; $i <= $days_elapsed; $i++) {

					# make a new date of the duedate
					$thisdate = new DateTime($debtor['duedate']);
					# add X days to this date
					$thisdate->add(new DateInterval('P' . $i . 'D'));
					# reformat it to Y-m-d
					$thisdate = $thisdate->format('Y-m-d');

					$refrate = 0;
					# $refdate = '';
					# walk rates from top to bottom
					foreach ($referencerate as $raterow) {
						# if the current date is bigger or the same, then take this
						if (strtotime($thisdate) >= strtotime($raterow['updated'])) {
							# $refdate = $raterow['updated'];
							$refrate = $raterow['rate'];
							break;
						}
					}

					$interest += (($debtor['amount']) * ($debtor['percentage'] + $refrate)) / 365;
					# echo 'Ränta för '.$thisdate.' beräknas på '.$refdate.' '.$refrate.' - > '. ($debtor['percentage'] + ($refrate * 0.01))."\n";
				}

				# summarize all costs
				$total = $debtor['amount'];
				$total += $interest;
				$total += $debtor['collectioncost'];
				$total += $debtor['remindercost'];

				# placeholder that must exist in template
				$placeholders_must_exist = array(
					'$AMOUNT$',
					'$DUEDATE$',
					'$INVOICEDATE$',
					# '$INVOICENUMBER$',
					'$PERCENTAGE$',
					'$TOTAL$'
				);

				# fill placeholders
				$placeholders = array(
					'$ADDRESS$' => $debtor['address'],
					'$AMOUNT$' => number_format($debtor['amount'], 2, ',',','),
					'$CITY$' => $debtor['city'],
					'$COLLECTIONCOST$' => number_format(
						$debtor['collectioncost'], 2
					),
					'$DUEDATE$' => $debtor['duedate'],
					'$EMAIL$' => $debtor['email'],
					'$INTERESTDATE$' => date('Y-m-d'),
					'$INTEREST$' => number_format($interest, 2, ',',','),
					'$INTERESTRAISE$' => number_format($perday, 2, ',',','),
					'$INVOICEDATE$' => $debtor['invoicedate'],
					'$NAME$' => $debtor['name'],
					'$INVOICENUMBER$' => $debtor['invoicenumber'],
					'$ORGNO$' => $debtor['orgno'],
					'$PERCENTAGE$' => number_format(
						$debtor['percentage'] * 100, 2
					),
					'$REMINDERCOST$' => number_format(
						$debtor['remindercost'], 2
					),
					'$TOTAL$' => number_format($total, 2, ',',','),
					'$ZIPCODE$' => $debtor['zipcode']
				);

				# log it
				cl($link, VERBOSE_DEBUG, 'Filling template placeholders', $debtor['id']);

				# make sure all locations exist
				foreach ($placeholders as $placeholderk => $placeholderv) {

					# does this placeholder not exist in the template body?
					if (strpos($template, $placeholderk) === false) {

						# is it a required value?
						if (in_array($placeholderk, $placeholders_must_exist)) {
							# disable this debtor
							set_debtor_status(
								$link,
								$debtor['id'],
								DEBTOR_STATUS_ERROR
							);

							# log it
							cl(
								$link,
								VERBOSE_ERROR,
								TEMPLATE_DIR.$debtor['template'].
									' is missing placeholder '.
									$placeholderk,
								$debtor['id']
							);
							# take next debtor
							continue 2;

						}

						# take next placeholder
						continue 1;
					}

					# fill the placeholder
					$template = str_replace($placeholderk, $placeholderv, $template);
				}

				# find subject
				$subject = trim(substr($template, 0, strpos($template, '---')));

				# find body
				$body = trim(substr($template, strpos($template, '---') + 3));

				# log it
				cl($link, VERBOSE_DEBUG, 'Sending mail to: '.$debtor['email'], $debtor['id']);

				# to send HTML mail, the Content-type header must be set
				$headers[] = 'MIME-Version: 1.0';
				$headers[] = 'Content-type: text/plain; charset=UTF-8';

				# additional headers
				# $headers[] = 'To: Mary <mary@example.com>';
				$headers[] = 'From: '.MAIL_ADDRESS_FROM;
				# $headers[] = 'Reply-To: '.MAIL_ADDRESS_FROM;

				# is there a bcc address supplied
				if (strlen($debtor['email_bcc'])) {
					# then add the bcc header
					$headers[] = 'Bcc: '.$debtor['email_bcc'];
				}

				cl(
					$link,
					VERBOSE_DEBUG,
					"\n".
						str_repeat('-', 80)."\n".
						implode(
							str_repeat('-', 80)."\n",
							array(
								'To: '.$debtor['email']."\n".implode("\n", $headers)."\n",
								$subject."\n",
								$body
							)
						)."\n".
						str_repeat('-', 80)."\n",
					$debtor['id']
				);

				# try to send the mail
				if (!$config_opt['main']['dryrun']) {
					$mail_sent = mail(
						$debtor['email'],
						$subject,
						$body,
						implode("\r\n", $headers)
					);
				} else {
					$mail_sent = true;
				}

				# did mail fail?
				if ($mail_sent === false) {
					# disable this debtor
					set_debtor_status(
						$link,
						$debtor['id'],
						DEBTOR_STATUS_ERROR
					);

					# log it
					cl(
						$link,
						VERBOSE_ERROR,
						'Failed sending mail to '.
							$debtor['email'].' (bcc: '.
							$debtor['email_bcc'].')',
						$debtor['id']
					);

					# take next debtor
					continue;
				}

				# update last reminder on this debtor
				$sql = '
					UPDATE
						invoicenagger_debtors
					SET
						updated="'.dbres($link, date('Y-m-d H:i:s')).'",
						last_reminder="'.dbres($link, date('Y-m-d H:i:s')).'",
						mails_sent=mails_sent+1
					WHERE
						id="'.dbres($link, $debtor['id']).'"
					';
				cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
				$r = db_query($link, $sql);
				if ($r === false) {
					cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
					die(1);
				}

				# log that mail has been sent
				cl($link, VERBOSE_INFO, 'Mail sent: '.$debtor['email'], $debtor['id']);

			} while (true);

			die(0);

		case 'remindreset': # to reset last reminded dates on active debtors

			# log it
			cl(
				$link,
				VERBOSE_INFO,
				'Resetting all active debtor reminder dates.'
			);

			$sql = '
				UPDATE
					invoicenagger_debtors
				SET
					last_reminder="'.dbres($link, '1970-01-01 00:00:00').'"
				WHERE
					status="'.dbres($link, DEBTOR_STATUS_ACTIVE).'"';
			cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
			$r = db_query($link, $sql);
			if ($r === false) {
				cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
				die(1);
			}
			die(0);
		case 'updatereference':
			cl(
				$link,
				VERBOSE_DEBUG,
				'Updating reference table for Riksbanken reference rate'
			);
			get_reference_rate($link);
			break;
	}
?>
