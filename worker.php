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
  # 2018-02-13 18:30:00 - adding clear reference rate action
  # 2018-07-28 16:13:32 - indentation change, tab to 2 spaces
  # 2018-07-28 17:02:00 - renaming from invoicenagger to invoicereminder
  # 2018-07-30 00:00:00 - adding balance
  # 2018-07-31 00:00:00 - adding balance
  # 2018-08-01 18:50:00 - adding balance

  require_once('include/functions.php');

  # change dir to the same as the script
  chdir(dirname(__FILE__));

  $opts = getopt('a:dhv:', array('action:', 'dryrun', 'help', 'verbose:'));

  $action = false;

  # default config
  $config = array(
    'main' => array(
      'dryrun' => false,
      'loglevel' => VERBOSE_INFO,
      'verbose' => VERBOSE_ERROR
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
    case 'clearreference':
      $sql = 'SELECT * FROM invoicereminder_riksbank_reference_rate ORDER BY updated DESC,id DESC';
      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      foreach ($r as $item) {
        $sql = '
        DELETE
        FROM
          invoicereminder_riksbank_reference_rate
        WHERE
          id > '.dbres($link, $item['id']).' AND
          updated="'.dbres($link, $item['updated']).'" AND
          CAST(rate AS CHAR) = "'.dbres($link, $item['rate']).'"';
        $rsub = db_query($link, $sql);
        if ($rsub === false) {
          cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
          die(1);
        }
      }
      break;

    case 'errorreset': # to reset all errors

      # log it
      cl(
        $link,
        VERBOSE_INFO,
        'Resetting all errored debtors to active.'
      );

      $sql = '
        UPDATE
          invoicereminder_debtors
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
          invoicereminder_riksbank_reference_rate
        ORDER BY updated DESC
        ';
      $referencerate = db_query($link, $sql);

      # log it
      cl(
        $link,
        VERBOSE_DEBUG,
        'Searching for debtors to remind'
      );

      # get all active debtors with active status and not reminded yet
      $sql = '
        SELECT
          id, email_bcc
        FROM
          invoicereminder_debtors
        WHERE
          status='.dbres($link, DEBTOR_STATUS_ACTIVE).'
          AND
          last_reminder <= timestampadd(day, -reminder_days,now())
          AND (
            day_of_month = 0
            OR
            DAYOFMONTH(NOW()) >= day_of_month
          )
        ';

      cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
      $debtors = db_query($link, $sql);
      if ($debtors === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      # no debtor found?
      if (!count($debtors)) {
        # log it
        cl($link, VERBOSE_DEBUG, 'No unreminded debtors found');
        break;
      }

      foreach ($debtors as $debtor) {

        $mail = compose_mail($link, $debtor['id']);

        if (!$mail) {
          continue;
        }

        # log it
        cl($link, VERBOSE_DEBUG, 'Sending mail to: '.$mail['to'], $debtor['id']);

        # try to send the mail
        if (!$config_opt['main']['dryrun']) {
        /*
          $mail_sent = mail(
            $mail['to'],
            $mail['subject'],
            $mail['body'],
            implode("\r\n", $mail['headers'])
          );
          */
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
              $mail['to'].' (bcc: '.
              $debtor['email_bcc'].')',
            $debtor['id']
          );

          # take next debtor
          continue;
        }

        if (!$config_opt['main']['dryrun']) {
          # update last reminder on this debtor
          $sql = '
            UPDATE
              invoicereminder_debtors
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
        }

        # log that mail has been sent
        cl($link, VERBOSE_INFO, 'Mail sent: '.$mail['to'], $debtor['id']);
      }

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
          invoicereminder_debtors
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
        'Updating reference table for Riksbanken reference rate.'
      );
      get_reference_rate($link);
      break;
  }
?>
