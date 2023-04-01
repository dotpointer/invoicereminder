<?php
  session_start();
  # changelog
  # 2017-02-14 17:57:26 - initial version
  # 2017-02-17 00:54:02 - updating
  # 2017-02-17 01:20:43 - bugfix working dir
  # 2017-02-17 23:49:31 - adding log
  # 2017-12-09 20:53:00 - adding Riksbanken reference rate
  # 2018-02-13 18:38:00 - updating Riksbanken reference rate fetcher due to source changes
  # 2018-07-28 16:13:32 - indentation change, tab to 2 spaces
  # 2018-07-28 17:02:00 - renaming from invoicenagger to invoicereminder
  # 2018-07-28 17:39:00 - moving sql to separate file
  # 2018-07-30 00:00:00 - adding balance
  # 2018-07-31 00:00:00 - adding balance
  # 2018-08-01 18:43:00 - adding balance
  # 2018-08-04 13:38:00 - translations
  # 2018-08-06 00:00:00 - adding balance
  # 2018-08-07 19:15:00 - adding balance
  # 2018-08-07 20:28:00 - adding balance
  # 2018-08-08 17:04:00 - adding balance
  # 2018-08-08 17:18:00 - renaming properties table
  # 2018-08-08 17:43:00 - moving setup downwards to make verbosity constants available
  # 2018-11-06 21:48:00 - renaming debts table and columns
  # 2018-11-12 17:51:00 - separating debt and debtor
  # 2018-11-12 19:27:00 - implementing contacts
  # 2018-12-20 18:49:00 - moving translation to Base translate
  # 2018-12-25 03:18:00 - adding SOAP version of reference rate fetcher
  # 2023-04-01 18:26:00 - adding reference rate update check

  define('SITE_SHORTNAME', 'invoicereminder');

  define('BALANCE_TYPE_NORMAL', 0);
  define('BALANCE_TYPE_DUEDATE', 1);

  define('DEBT_STATUS_ACTIVE', 1);
  define('DEBT_STATUS_ERROR', -1);
  define('DEBT_STATUS_INACTIVE', 0);

  define('LOG_TYPE_ERROR', -1);
  define('LOG_TYPE_MAIL_SENT', 1);

  define('TEMPLATE_DIR', 'templates/');

  # verbosity
  define('VERBOSE_OFF', 0);		# no info at all
  define('VERBOSE_ERROR', 1);		# only errors
  define('VERBOSE_INFO', 2);		# above and things that changes
  define('VERBOSE_DEBUG', 3);		# above and verbose info
  define('VERBOSE_DEBUG_DEEP', 4);

  require_once('setup.php');

  $available_properties = array(
    '$BANK-ACCOUNT-CLEARING-NUMBER-CREDITOR$',
    '$BANK-ACCOUNT-NUMBER-CREDITOR$',
    '$BANK-NAME-CREDITOR$',
    '$CELLPHONE-NUMBER-CREDITOR$'
  );

  require_once('base3.php');
  require_once('base.translate.php');

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

  function get_balance_cost($balance_history, $thisdate) {
    $cost_this_day = 0;
    foreach ($balance_history as $balance) {
      # if the current date is bigger or the same, then take this
      if (strtotime($thisdate) == strtotime($balance['happened'])) {
        # note, there can be multiple costs for this day
        $cost_this_day += $balance['cost'];

        # something has changed

      }
    }
    return $cost_this_day;
  }

  # get latest reference rate for a specific date
  function get_balance_latest_refrate($link, $thisdate) {

    # static storage, kept on next function run
    static $referencerates = false;

    # init static variable, done first run
    if ($referencerates === false) {
      # get reference rate, descending
      $sql = '
        SELECT
          *
        FROM
          invoicereminder_riksbank_reference_rate
        ORDER BY updated DESC
        ';
      $referencerates = db_query($link, $sql);
    }

    $refrate = 0;
    # go BACKWARDS, from latest to earliest
    foreach ($referencerates as $raterow) {
      # if the current date is bigger or the same, then take this
      if (strtotime($thisdate) >= strtotime($raterow['updated'])) {
        $refrate = $raterow['rate'];
        break;
      }
    }
    return $refrate;
  }

  function get_balance_mails_sent($link, $id_debts, $thisdate) {
    static $log = false;

    # init of static variable, done first run
    if ($log === false) {
      $sql = '
        SELECT
          *
        FROM
          invoicereminder_log
        WHERE
          id_debts='.dbres($link, $id_debts).'
          AND
          type=2
        ORDER BY created
        ';
      $log = db_query($link, $sql);
    }

    $mails_sent = 0;
    foreach ($log as $row) {
      # if the current date is the same, then take this
      if (date('Y-m-d', strtotime($thisdate)) == date('Y-m-d', strtotime($row['created']))) {
        $mails_sent += 1;
        break;
      }
    }
    return $mails_sent;
  }

  function get_balance_payment($balance_history, $thisdate) {
    $payment_this_day = 0;
    foreach ($balance_history as $balance) {

      # if the current date is bigger or the same, then take this
      if (strtotime($thisdate) == strtotime($balance['happened'])) {
        # note, there can be multiple payments for this day
        $payment_this_day += $balance['payment'];
      }
    }
    return $payment_this_day;
  }

  function balance_is_this_duedate($balance_history, $thisdate) {
    foreach ($balance_history as $balance) {
      # if the current date is bigger or the same, then take this
      if (strtotime($thisdate) == strtotime($balance['happened'])) {
        # is this a due date
        if ((int)$balance['type'] === BALANCE_TYPE_DUEDATE) {
          return true;
        }
      }
    }
    return false;
  }

  function get_balance_messages($balance_history, $message, $thisdate) {
    foreach ($balance_history as $balance) {
      # if the current date is bigger or the same, then take this
      if (strtotime($thisdate) == strtotime($balance['happened'])) {
        # is this a due date
        if ((int)$balance['type'] === BALANCE_TYPE_DUEDATE) {
          $message[] = t('Due date');
        }

        # get messages
        if (
          strlen($balance['message'])
        ) {
          $message[] = $balance['message'];
        }
      }
    }
    return $message;
  }

  function balance_history($link, $id_debts, $parameters=false) {

    $send_mail = true;

    # get the debt
    $sql = '
      SELECT
        *
      FROM
        invoicereminder_debts
      WHERE
        id='.dbres($link, $id_debts).'
      LIMIT 1;
      ';
    cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
    $debt = db_query($link, $sql);
    if ($debt === false) {
      cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
      die(1);
    }

    # no debt found?
    if (!count($debt) || !isset($debt[0])) {
      return false;
    }

    # simplify debt
    $debt = reset($debt);

    # get the debt balance history
    # get reference rate, descending
    $sql = '
      SELECT
        *
      FROM
        invoicereminder_balance
      WHERE
        id_debts='.dbres($link, $id_debts).'
      ORDER BY happened
      ';
    $balance_history = db_query($link, $sql);

    # last date in balance
    $lastbalancedate = count($balance_history) ? end($balance_history)['happened'] : false;

    $duedate = false;
    foreach ($balance_history as $row) {
      if ((int)$row['type'] === BALANCE_TYPE_DUEDATE) {
        $duedate = date('Y-m-d', strtotime($row['happened']));
        break;
      }
    }

    $summarization = array();
    $date_last_year_end = date('Y-m-d', strtotime("Last day of December", mktime(0, 0, 0, date('m'), date('d'), date('Y') - 1)));
    $total_last_year_end = 0;

    if ($duedate) {

      # start date and end date for interest calculation
      $date1 = new DateTime($duedate);
      $date2 = isset($parameters['dateto']) && $parameters['dateto'] ? new DateTime($parameters['dateto']) : new DateTime();

      # no need to remove one day here, as PHP does not add one
      $days_elapsed = $date2->diff($date1)->format("%a");

      # calculate interest for all days that has elapsed
      $amount_accrued = 0;
      $amount_this_day = 0;
      $cost_accrued = 0;
      $cost_this_day = 0;
      $interest_accrued = 0;
      $interest_this_day = 0;
      $last_refrate = false;
      $payment_accrued = 0;

      # walk the days
      for ($i=0; $i <= $days_elapsed; $i++) {

        # any changes this day?
        $changesthisday = false;

        # messages for this day container
        $message = array();

        # make a new date of the duedate
        $thisdate = new DateTime($duedate);

        # add X days to this date
        $thisdate->add(new DateInterval('P' . $i . 'D'));

        # reformat it to Y-m-d
        $thisdate = $thisdate->format('Y-m-d');

        $duedate_passed = strtotime($thisdate) > strtotime($duedate);

        # --- duedate
        if (balance_is_this_duedate($balance_history, $thisdate)) {
          $changesthisday = true;
        }

        # --- reference rate

        # find last reference rate
        $refrate = get_balance_latest_refrate($link, $thisdate);
        if ($last_refrate !== $refrate) {
          $last_refrate = $refrate;
          $changesthisday = true;
        }

        # --- mails
        # find mail sendings
        $mails_sent = get_balance_mails_sent($link, $id_debts, $thisdate);
        # --- payment

        # walk payments calculate to this day
        $message = array();
        $payment_paid_this_day = 0;
        $payment_this_day = get_balance_payment($balance_history, $thisdate);
        if ($payment_this_day !== 0) {
          $changesthisday = true;
        }

        # add daily payments to the accrued one
        $payment_accrued += $payment_this_day;

        # --- amount

        # find current amount to calculate on
        $amount_this_day = 0;
        foreach ($balance_history as $balance) {
          # if the current date is bigger or the same, then take this
          if (strtotime($thisdate) == strtotime($balance['happened'])) {
            $amount_this_day += $balance['amount'];

            # something has changed
            if ($amount_this_day !== 0) {
              $changesthisday = true;
            }
          }

        }

        # add daily amount to the accrued one
        $amount_accrued += $amount_this_day;
        $amount_paid_this_day = 0;
        # is there payment left and amount is more than 0?
        if ($payment_accrued > 0 && $amount_accrued > 0) {
          $changesthisday = true;
          # more amount left than payment
          if ($amount_accrued >= $payment_accrued) {
            # use the full payment
            $amount_accrued -= $payment_accrued;
            $amount_paid_this_day = $payment_accrued;
            $payment_paid_this_day += $payment_accrued;
            $payment_accrued = 0;
          } else {
            # reduce the payment left with the amount accrued
            $amount_paid_this_day = $amount_accrued;
            $payment_accrued = $payment_accrued - $amount_accrued;
            $payment_paid_this_day += $amount_accrued;
            $amount_accrued = 0;
          }
        }

        # --- interest - depends on amount
        $interest_this_day = 0;
        $interest_paid_this_day = 0;
        $rate = 0;
        # calculate interest for one day - if there is amount to calculate on
        if ($amount_accrued > 0 && $duedate_passed) {

          $rate = $debt['percentage'];

          # no changes this day - interest goes up all the time
          $interest_this_day = (($amount_accrued) * ($rate + $refrate)) / (date('z', mktime(0, 0, 0, 12, 31, date('Y', strtotime($thisdate)))) + 1);
          $interest_accrued += $interest_this_day;
        # no amount to calculate on, then check if there is payment left to reduce interest
        } else {
          $refrate = 0;
          # is there payment left and interest is more than 0?
          if ($payment_accrued > 0 && $interest_accrued > 0) {
            $changesthisday = true;

            # more interest left than payment
            if ($interest_accrued >= $payment_accrued) {
              # use the full payment
              $interest_accrued -= $payment_accrued;
              $interest_paid_this_day = $payment_accrued;
              $payment_paid_this_day += $payment_accrued;
              $payment_accrued = 0;
            } else {
              # reduce the payment left with the interest accrued
              $interest_paid_this_day = $interest_accrued;
              $payment_paid_this_day += $interest_accrued;
              $payment_accrued = $payment_accrued - $interest_accrued;
              $interest_accrued = 0;
            }
          }
        }

        # --- cost

        # walk interestless costs calculate to this day
        $cost_this_day = get_balance_cost($balance_history, $thisdate);
        if ($cost_this_day !== 0) {
          $changesthisday = true;
        }

        $message = get_balance_messages($balance_history, $message, $thisdate);

        # add daily costs to the accrued one
        $cost_accrued += $cost_this_day;
        $cost_paid_this_day = 0;

        # is there payment left and cost is more than 0?
        if ($payment_accrued > 0 && $cost_accrued > 0) {
          $changesthisday = true;
          # more cost left than payment
          if ($cost_accrued >= $payment_accrued) {
            # use the full payment
            $cost_accrued -= $payment_accrued;
            $cost_paid_this_day = $payment_accrued;
            $payment_paid_this_day += $payment_accrued;
            $payment_accrued = 0;
          } else {
            # reduce the payment left with the cost accrued
            $cost_paid_this_day = $cost_accrued;
            $payment_paid_this_day += $cost_accrued;
            $payment_accrued = $payment_accrued - $cost_accrued;
            $cost_accrued = 0;
          }
        }

        # --- last year summary

        # is this before or up to last years last day?
        if (strtotime($thisdate) <= strtotime($date_last_year_end)) {
          $total_last_year_end = $amount_accrued;
          $total_last_year_end += $interest_accrued;
          $total_last_year_end += $cost_accrued;
        }

        # --- day summary

        # add this day to the history
        $summarization[] = array(
          'amount_accrued' => $amount_accrued,
          'amount_paid_this_day' => $amount_paid_this_day,
          'amount_this_day' => $amount_this_day,
          'changesthisday' => $changesthisday,
          'cost_accrued' => $cost_accrued,
          'cost_paid_this_day' => $cost_paid_this_day,
          'cost_this_day' => $cost_this_day,
          'date' => $thisdate,
          'interest_accrued' => $interest_accrued,
          'interest_paid_this_day' => $interest_paid_this_day,
          'interest_this_day' => $interest_this_day,
          'mails_sent' => $mails_sent,
          'message' => implode(' ', $message),
          'payment_accrued' => $payment_accrued,
          'payment_paid_this_day' => $payment_paid_this_day,
          'payment_this_day' => $payment_this_day,
          'rate' => $rate,
          'refrate' => $refrate,
          'rate_accrued' => $rate + $refrate,
          'total' => $amount_accrued + $interest_accrued + $cost_accrued - $payment_accrued
        );

        # check if all is paid and at the end of balance
        if (
          $amount_accrued === 0 &&
          $interest_accrued === 0 &&
          round($cost_accrued, 2) === 0.00 && # cost ends in precision debt without rate
          strtotime($lastbalancedate) <= strtotime($thisdate)
        ) {
          # then stop
          $send_mail = false;
          break;
        }
      }
    } # if-duedate

    return array(
      'history' => $summarization,
      'special' => array(
        'date_last_year_end' => $date_last_year_end,
        'duedate' => $duedate,
        'send_mail' => $send_mail,
        'total_last_year_end' => $total_last_year_end
      )
    );
  }

  function compose_mail($link, $id_debts, $templatefile=false, $force=false, $dateto=false) {
    # get all active debts with active status and not reminded yet
    $sql = '
      SELECT
        creditors.address AS address_creditor,
        creditors.city AS city_creditor,
        creditors.company AS company_creditor,
        creditors.email AS email_creditor,
        creditors.email_bcc AS email_bcc_creditor,
        creditors.name AS name_creditor,
        creditors.orgno AS orgno_creditor,
        creditors.phonenumber AS phonenumber_creditor,
        creditors.zipcode AS zipcode_creditor,
        debtors.address AS address_debtor,
        debtors.city AS city_debtor,
        debtors.company AS company_debtor,
        debtors.email AS email_debtor,
        debtors.email_bcc AS email_bcc_debtor,
        debtors.name AS name_debtor,
        debtors.orgno AS orgno_debtor,
        debtors.phonenumber AS phonenumber_debtor,
        debtors.zipcode AS zipcode_debtor,
        debts.amount,
        debts.collectioncost,
        debts.day_of_month,
        debts.duedate,
        debts.id,
        debts.id_contacts_creditor AS id_creditor,
        debts.id_contacts_debtor AS id_debtor,
        debts.invoicedate,
        debts.invoicenumber,
        debts.last_reminder,
        debts.mails_sent,
        debts.percentage,
        debts.reminder_days,
        debts.remindercost,
        debts.status,
        debts.template
      FROM
        invoicereminder_debts AS debts
          LEFT JOIN
            invoicereminder_contacts AS creditors ON debts.id_contacts_creditor = creditors.id
          LEFT JOIN
            invoicereminder_contacts AS debtors ON debts.id_contacts_debtor = debtors.id
      WHERE
        debts.id='.dbres($link, $id_debts).'
      ';

    cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
    $debts = db_query($link, $sql);
    if ($debts === false) {
      cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
      die(1);
    }

    # no debt found?
    if (!count($debts)) {
      cl($link, VERBOSE_DEBUG, 'Debt with id '.$id_debts.' not found');
      return false;
    }

    $debt = $debts[0];

    # get properties
    $sql = '
      SELECT
        *
      FROM
        invoicereminder_properties
      ';

    cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
    $properties = db_query($link, $sql);
    if ($properties === false) {
      cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
      die(1);
    }

    if (!$templatefile) {
      # get the template
      $templatefile = TEMPLATE_DIR.$debt['template'];
    }

    # log it
    cl($link, VERBOSE_DEBUG, 'Using template: '.$templatefile, $debt['id']);

    # no template file, fatal error
    if (!$templatefile) {

      if ($templatefile === $debt['template']) {
        # disable all debts with this template
        disable_debts_with_template($link, $debt['template']);
      }

      # log it
      cl(
        $link,
        VERBOSE_ERROR,
        TEMPLATE_DIR.$debt['template'].' does not exist',
        $debt['id']
      );

      # take next debt
      return false;
    }

    # get template file
    $template = file_get_contents($templatefile);

    # failed reading template file
    if ($template === false) {

      # disable all debts with this template
      disable_debts_with_template($link, $debt['template']);

      # log it
      cl(
        $link,
        VERBOSE_ERROR,
        TEMPLATE_DIR.$debt['template'].' is not readable',
        $debt['id']
      );

      # take next debt
      return false;
    }

    $template = trim($template);

    # failed reading template file
    if (!strlen($template)) {

      # disable all debts with this template
      disable_debts_with_template($link, $debt['template']);

      # log it
      cl(
        $link,
        VERBOSE_ERROR,
        TEMPLATE_DIR.$debt['template'].' is empty',
        $debt['id']
      );

      # take next debt
      return false;
    }

    # extract subject
    if (strpos($template, '---') === false) {

      # disable all debts with this template
      disable_debts_with_template($link, $debt['template']);

      # log it
      cl(
        $link,
        VERBOSE_ERROR,
        TEMPLATE_DIR.$debt['template'].
        ' is missing subject/body divider ---',
        $debt['id']
      );

      # take next debt
      return false;
    }

    $parameters = array();

    if ($dateto) {
      $parameters['dateto'] = $dateto;
    }

    $balance_history = balance_history($link, $debt['id'], $parameters);

    if (!$force && !$balance_history['special']['send_mail']) {
      # log it
      cl($link, VERBOSE_DEBUG, 'Disabling debt due to satisfied balance history.', $debt['id']);
      # disable this debt
      set_debt_status(
        $link,
        $debt['id'],
        DEBT_STATUS_INACTIVE
      );
      return false;
    }

    $lastrow = end($balance_history['history']);

    # placeholder that must exist in template
    $placeholders_must_exist = array(
      '$AMOUNT-ACCRUED$',
      '$RATE-ACCRUED$',
      '$TOTAL$'
    );

    # fill placeholders
    $placeholders = array(
      '$ADDRESS-CREDITOR$' => $debt['address_creditor'],
      '$ADDRESS-DEBTOR$' => $debt['address_debtor'],
      '$AMOUNT-ACCRUED$' => money($lastrow['amount_accrued']),
      '$CITY-CREDITOR$' => $debt['city_creditor'],
      '$CITY-DEBTOR$' => $debt['city_debtor'],
      '$COMPANY-NAME-CREDITOR$' => $debt['company_creditor'],
      '$COMPANY-NAME-DEBTOR$' => $debt['company_debtor'],
      '$COST-ACCRUED$' => money($lastrow['cost_accrued']),
      '$DUE-DATE$' => $balance_history['special']['duedate'],
      '$EMAIL-CREDITOR$' => $debt['email_creditor'],
      '$EMAIL-DEBTOR$' => $debt['email_debtor'],
      '$INTEREST-ACCRUED$' => money($lastrow['interest_accrued']),
      '$INTEREST-DATE$' => $dateto ? $dateto : date('Y-m-d'),
      '$INTEREST-PER-DAY$' => money($lastrow['interest_this_day']),
      '$INVOICE-DATE$' => $debt['invoicedate'],
      '$INVOICE-NUMBER$' => $debt['invoicenumber'],
      '$NAME-CREDITOR$' => $debt['name_creditor'],
      '$NAME-DEBTOR$' => $debt['name_debtor'],
      '$ORGNO-CREDITOR$' => $debt['orgno_creditor'],
      '$ORGNO-DEBTOR$' => $debt['orgno_debtor'],
      '$PHONE-NUMBER-CREDITOR$' => $debt['phonenumber_creditor'],
      '$PHONE-NUMBER-DEBTOR$' => $debt['phonenumber_debtor'],
      '$RATE-ACCRUED$' => percentage(($lastrow['rate_accrued']) * 100, 2),
      '$TOTAL$' => money($lastrow['amount_accrued'] + $lastrow['interest_accrued'] + $lastrow['cost_accrued']),
      '$ZIP-CODE-CREDITOR' => $debt['zipcode_creditor'],
      '$ZIP-CODE-DEBTOR$' => $debt['zipcode_debtor']
    );

    # complement with properties from database
    foreach ($properties as $property) {
      $placeholders[$property['property']] = $property['value'];
    }

    # log it
    cl($link, VERBOSE_DEBUG, 'Filling template placeholders', $debt['id']);

    # make sure all locations exist
    foreach ($placeholders as $placeholderk => $placeholderv) {

      # does this placeholder not exist in the template body?
      if (strpos($template, $placeholderk) === false) {

        # is it a required value?
        if (in_array($placeholderk, $placeholders_must_exist)) {
          # disable this debt
          set_debt_status(
            $link,
            $debt['id'],
            DEBT_STATUS_ERROR
          );

          # log it
          cl(
            $link,
            VERBOSE_ERROR,
            TEMPLATE_DIR.$debt['template'].
              ' is missing placeholder '.
              $placeholderk,
            $debt['id']
          );
          # take next debt
          return false;
        }

        # take next placeholder
        continue;
      }

      # fill the placeholder
      $template = str_replace($placeholderk, $placeholderv, $template);
    }

    # find subject
    $subject = trim(substr($template, 0, strpos($template, '---')));

    # find body
    $body = trim(substr($template, strpos($template, '---') + 3));

    # to send HTML mail, the Content-type header must be set
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/plain; charset=UTF-8';

    # additional headers
    # $headers[] = 'To: Mary <mary@example.com>';
    $headers[] = 'From: '.MAIL_ADDRESS_FROM;
    # $headers[] = 'Reply-To: '.MAIL_ADDRESS_FROM;

    # is there a bcc address supplied
    if (strlen($debt['email_bcc_debtor'])) {
      # then add the bcc header
      $headers[] = 'Bcc: '.$debt['email_bcc_debtor'];
    }

    cl(
      $link,
      VERBOSE_DEBUG,
      "\n".
        str_repeat('-', 80)."\n".
        implode(
          str_repeat('-', 80)."\n",
          array(
            'To: '.$debt['email_debtor']."\n".implode("\n", $headers)."\n",
            $subject."\n",
            $body
          )
        )."\n".
        str_repeat('-', 80)."\n",
      $debt['id']
    );

    return array(
      'headers' => $headers,
      'templatefile' => basename($templatefile),
      'templatefile_default' => basename($debt['template']),
      'to' => $debt['email_debtor'],
      'subject' => $subject,
      'body' => $body
    );
  }

  # debug printing
  function cl($link, $level, $s, $id_debts=0) {

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
      echo date('Y-m-d H:i:s').' '.$l.' #'.$id_debts.' '.$s."\n";
    }

    # is loglevel on and level is enough - the try to append to log
    if (
      $config['main']['loglevel'] &&
      $config['main']['loglevel'] >= $level &&
      $link
    ) {

      # log the error
      $iu = dbpia($link, array(
        'created' => date('Y-m-d H:i:s'),
        'id_debts' => $id_debts,
        'message' => $s,
        'type' => $level
      ));
      $sql = 'INSERT INTO invoicereminder_log ('.implode(',', array_keys($iu)).') VALUES('.implode(',', $iu).')';
      $r = db_query($link, $sql);
      if ($r === false) {
        echo db_error($link);
        die(1);
      }

    }

    return true;
  }

  # to disable all debts with a certain template
  function disable_debts_with_template($link, $template) {
    # disable this debt
    $sql = '
      UPDATE
        invoicereminder_debts
      SET
        status="'.dbres($link,DEBT_STATUS_ERROR).'",
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

  function get_reference_rate($link) {

/*
    # 2018-12-25 03:15 - SOAP version, for fallback
    # does however only supply weekday dates

    $client = new SoapClient("https://swea.riksbank.se/sweaWS/wsdl/sweaWS_ssl.wsdl");

    $params = array(
      'searchRequestParameters' => array(
        'aggregateMethod' => 'D',
        'avg' => false,
        'datefrom' => '2002-07-01',
        'dateto' => date('Y-m-d'),
        'languageid' => 'sv',
        'max' => false,
        'min' => false,
        'searchGroupSeries' => array(
          array(
            'groupid' => 3,
            'seriesid' => 'SECBREFEFF'
          )
        ),
        'ultimo' => false
      )
    );

    $response = $client->__soapCall("getInterestAndExchangeRates", array($params));

    # file_put_contents('loader.txt', json_encode($response));
    # $response = file_get_contents('loader.txt');
    # $response = json_decode($response, true);
    # $response = json_decode(json_encode($response), true);

    $latestrate = false;
    foreach ($response['return']['groups']['series']['resultrows'] as $k => $row) {
      # echo $row['date']."\t".$row['value']."\n";

      if ($row['value'] === $latestrate) {
        continue;
      }
      $latestrate = $row['value'];
      $date = $row['date'];
      $rate = (float)str_replace(',', '.', $row['value']) * 0.01;
      # debug
      # echo ($k + 1).': "'.$date.'" "'.$rate.'"'."\n";

      $sql = '
        SELECT
          *
        FROM
          invoicereminder_riksbank_reference_rate
        WHERE
          updated="'.dbres($link, $date).'"
        AND
          CAST(rate AS CHAR) = "'.dbres($link, $rate).'"
        ';
      # echo $sql."\n";
      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      if (!count($r)) {
        echo $date.' -> '.$rate."\n";
        $sql = '
          INSERT INTO invoicereminder_riksbank_reference_rate (
            updated, rate
          ) VALUES(
            "'.dbres($link, $date).'",
            "'.dbres($link, $rate).'"
          )
        '."\n";
        # echo $sql."\n";
        $r = db_query($link, $sql);
        if ($r === false) {
          cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
          die(1);
        }
      }
    }
*/

    # v1 - before 2018-02-13
    # $contents = file_get_contents('http://www.riksbank.se/sv/Rantor-och-valutakurser/Referensranta-och-tidigare-diskonto-tabell/');
    $contents = file_get_contents('https://www.riksbank.se/sv/statistik/sok-rantor--valutakurser/referensranta/');

    # debug
    # file_put_contents('include/ranta.txt', $contents);
    # $contents = file_get_contents('include/ranta.txt');

    if (!$contents) {
      echo 'Failed fetching reference rate page.'."\n";
      cl($link, VERBOSE_ERROR, 'Failed fetching reference rate page.');
      die(1);
    }

    # v1
    #$contents = substr(
    #	$contents,
    #	strpos($contents, '<strong>Referensränta</strong>'),
    #	strpos($contents, '<p class="tableheader" style="text-align: right;"><strong>Diskonto</strong></p>') - strpos($contents, '<strong>Referensränta</strong>')
    #);

    $contents = substr(
      $contents,
      strpos($contents, '<tr><th scope="col">Datum</th><th scope="col">Referensränta</th></tr>'),
      strpos($contents, '</table>')
    );

    if (!strlen($contents)) {
      echo 'Failed extracting part of reference rate page.'."\n";
      cl($link, VERBOSE_ERROR, 'Failed extracting part of reference rate page.');
      die(1);
    }

    # v1
    # $p = '/<td>(\d+[-]\d{2}[-]\d{2})<\/td>\s*<td align=\"right\" style=\"text-align: right;\">([-]?\d+,\d+)<\/td>/i';
    $p = '/<tr>\s*<td>\s*(?:&nbsp;)*\s*(\d+[-]\d{2}[-]\d{2})\s*(?:&nbsp;)*\s*<\/td>\s*<td>\s*(?:&nbsp;)*\s*([-]?\d+,\d+)\s*(?:&nbsp;)*\s*<\/td>/i';

    preg_match_all($p, $contents, $matches);
    foreach (array_reverse($matches[0]) as $k => $unused) {
      $date = $matches[1][$k];
      $rate = (float)str_replace(',', '.', $matches[2][$k]) * 0.01;

      # debug
      # echo ($k + 1).': "'.$date.'" "'.$rate.'"'."\n";

      $sql = 'SELECT * FROM invoicereminder_riksbank_reference_rate WHERE updated="'.dbres($link, $date).'" AND CAST(rate AS CHAR) = "'.dbres($link, $rate).'"';
      # echo $sql."\n";
      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      #echo count($r)."\n";

      if (!count($r)) {
        $sql = 'INSERT INTO invoicereminder_riksbank_reference_rate (updated, rate) VALUES("'.dbres($link, $date).'", "'.dbres($link, $rate).'")'."\n";
        # echo $sql."\n";
        $r = db_query($link, $sql);
        if ($r === false) {
          cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
          die(1);
        }
      }
    }
    return true;
  }

  function is_logged_in() {
    return true;
  }

  function is_reference_rate_updated($link) {
    $sql = 'SELECT updated FROM invoicereminder_riksbank_reference_rate ORDER BY updated DESC LIMIT 1';
    $r = db_query($link, $sql);
    if ($r === false) {
      cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
      die(1);
    }
    if (!count($r)) {
      return false;
    }
    $updated = strtotime($r[0]['updated']);
    $now = time();
    # from 01-01
    if ($now >= mktime(0, 0, 0, 1, 1) &&
      # before 07-01
      $now < mktime(0, 0, 0, 7, 1) &&
      # updated before 01-01
      $updated < mktime(0, 0, 0, 1, 1)
    ) {
      return false;
    }
    # from 07-01
    if ($now >= mktime(0, 0, 0, 7, 1) &&
      # before 12-31
      $now <= mktime(0, 0, 0, 12, 31) &&
      # updated before 07-01
      $updated < mktime(0, 0, 0, 7, 1)
    ) {
      return false;
    }
    return true;
  }

  function money($amount) {
    return number_format($amount, 2, ',', '');
  }

  function percentage($percentage) {
    return number_format($percentage, 2, ',', '');
  }

  # to disable a debt
  function set_debt_status($link, $id, $status) {
    # disable this debt
    $sql = '
      UPDATE
        invoicereminder_debts
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

?>
