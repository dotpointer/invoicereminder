<?php
	# changelog
	# 2017-02-14 17:31:52

	# to do a logmessage
	function logmessage($link, $type, $message) {
		# log the error
		$iu = dbpia($link, array(
			'message' => $message,
			'type' => $type,
			'created' => date('Y-m-d H:i:s')
		));
		$sql = 'INSERT INTO invoicenagger_log ('.implode(',', array_keys($iu)).') VALUES('.implode(',', $iu).')';
		$r = db_query($link, $sql);
		if ($r === false) {
			echo db_error($link);
			die(1);
		}
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

	require_once('include/functions.php');

	$opts = getopt('a:', array('action:'));

	$action = false;
	foreach ($opts as $k => $v) {
		switch ($k) {
			case 'a':
			case 'action':
				$action = $v;
				break;
		}
	}

	# walk actions
	switch ($action) {
		case 'remind':
			# get all active debtors
			$sql = '
				SELECT
					*
				FROM
					invoicenagger_debtors
				WHERE
					status='.DEBTOR_STATUS_ACTIVE.'
					AND
					last_reminder <= timestampadd(day, -reminder_days, now())
				';
			$debtors = 	db_query($link, $sql);

			# walk debtors
			foreach ($debtors as $debtor) {

				# get the template
				$templatefile = TEMPLATE_DIR.$debtor['template'];

				# no template file, fatal error
				if (!$templatefile) {
					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);
					# set_debtor_status($link, $debtor['id'], DEBTOR_STATUS_ERROR);

					# log it
					logmessage($link, LOG_TYPE_ERROR, TEMPLATE_DIR.$debtor['template'].' does not exist');

					die(1);
				}

				# get template file
				$template = file_get_contents($templatefile);

				# failed reading template file
				if ($template === false) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# disable this debtor
					# set_debtor_status($link, $debtor['id'], DEBTOR_STATUS_ERROR);

					# log it
					logmessage($link, LOG_TYPE_ERROR, TEMPLATE_DIR.$debtor['template'].' is not readable');

					die(1);
				}

				$template = trim($template);

				# failed reading template file
				if (!strlen($template)) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# disable this debtor
					# set_debtor_status($link, $debtor['id'], DEBTOR_STATUS_ERROR);

					# log it
					logmessage($link, LOG_TYPE_ERROR, TEMPLATE_DIR.$debtor['template'].' is empty');

					die(1);

				}

				# extract subject
				if (strpos($template, '---') === false) {

					# disable all debtors with this template
					disable_debtors_with_template($link, $debtor['template']);

					# disable this debtor
					# set_debtor_status($link, $debtor['id'], DEBTOR_STATUS_ERROR);

					# log it
					logmessage($link, LOG_TYPE_ERROR, TEMPLATE_DIR.$debtor['template'].' is missing subject/body divider ---');

					die(1);

				}

				# find subject
				$subject = trim(substr($template, 0, strpos($template, '---')));

				# find body
				$body = trim(substr($template, strpos($template, '---') + 3));

				# start date and end date for interest calculation
				$date1 = new DateTime($debtor['duedate']);
				$date2 = new DateTime();

				# no need to remove one day here, as PHP does not add one
				$days_elapsed = $date2->diff($date1)->format("%a");

				$perday = (($debtor['amount']) * $debtor['percentage']) / 365;


				$interest = $perday * $days_elapsed;

				$total = $debtor['amount'] + $interest + $debtor['collectioncost'];

				$placeholders = array(
					'$AMOUNT$' => number_format($debtor['amount'], 2),
					'$COLLECTIONCOST$' => number_format($debtor['collectioncost'], 2),
					'$DUEDATE$' => $debtor['duedate'],
					'$INTERESTDATE$' => date('Y-m-d'),
					'$INTEREST$' => number_format($interest, 2),
					'$INVOICEDATE$' => $debtor['invoicedate'],
					'$INVOICENUMBER$' => $debtor['invoicenumber'],
					'$PERCENTAGE$' => number_format($debtor['percentage'] * 100, 2),
					'$REMINDERCOST$' => number_format($debtor['remindercost'], 2),
					'$TOTAL$' => number_format($total, 2)
				);

				# make sure all locations exist
				foreach ($placeholders as $placeholderk => $placeholderv) {

					# does this placeholder not exist in the template body?
					if (strpos($body, $placeholderk) === false) {
						# disable this debtor
						set_debtor_status($link, $debtor['id'], DEBTOR_STATUS_ERROR);

						# log it
						logmessage($link, LOG_TYPE_ERROR, TEMPLATE_DIR.$debtor['template'].' is missing placeholder '.$placeholderk);

						die(1);

					}

					# fill the placeholder
					$body = str_replace($placeholderk, $placeholderv, $body);
				}

				# mail is ready, send it

				#echo 'SUBJECT: '.$subject."\n\n";
				#echo 'BODY: '."\n".$body."\n\n";

				# to send HTML mail, the Content-type header must be set
				$headers[] = 'MIME-Version: 1.0';
				$headers[] = 'Content-type: text/plain; charset=UTF-8';

				# additional headers
				# $headers[] = 'To: Mary <mary@example.com>';
				$headers[] = 'From: Your Name <your@email.com>';
				if (strlen($debtor['email_bcc'])) {
					$headers[] = 'Bcc: '.$debtor['email_bcc'];
				}

				# try to send the mail
				$mail_sent = mail(
					$debtor['email'],
					$subject,
					$body,
					implode("\r\n", $headers)
				);

				if ($mail_sent === false) {
					# disable this debtor
					set_debtor_status($link, $debtor['id'], DEBTOR_STATUS_ERROR);

					# log it
					logmessage($link, LOG_TYPE_ERROR, 'Failed sending mail to '.$debtor['email'].' (bcc: '.$debtor['email_bcc'].')');
					continue;
				}

				# update last reminder
				$sql = '
					UPDATE
						invoicenagger_debtors
					SET
						last_reminder="'.dbres($link, date('Y-m-d H:i:s')).'",
						mails_sent=mails_sent+1
					WHERE
						id="'.dbres($link, $debtor['id']).'"
					';
				$r = db_query($link, $sql);
				if ($r === false) {
					echo db_error($link);
					die(1);
				}

				logmessage($link, LOG_TYPE_MAIL_SENT, $debtor['email']);

			} # walk debtors
	}
?>
