<?php
  # changelog
  # 2017-02-14 17:31:52 - initial version
  # 2017-02-17 00:54:23 - updating
  # 2017-02-17 01:20:24 - bugfix working dir
  # 2017-02-17 23:57:27 - adding id_debts to log
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
  # 2018-08-08 17:05:00 - adding balance
  # 2018-11-06 21:48:00 - renaming table and columns
  # 2018-11-12 17:51:00 - separating debt and debtor
  # 2018-11-12 19:27:00 - implementing contacts
  # 2018-11-13 18:14:00 - adding missing email
  # 2018-11-26 19:13:00 - bugfix, reminders were queried correctly
  # 2023-04-01 18:26:00 - adding reference rate update check

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
      To reset all errors on all debts with status error by setting
      them back to active. (Does not change inactivated debts)
    remindreset
      To reset all last reminded dates on all active debts.
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
        'Resetting all errored debts to active.'
      );

      $sql = '
        UPDATE
          invoicereminder_debts
        SET
          status="'.dbres($link, DEBT_STATUS_ACTIVE).'"
        WHERE
          status="'.dbres($link, DEBT_STATUS_ERROR).'"';
      cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }
      die(0);

    case 'remind':

      if (!is_reference_rate_updated($link)) {
        cl(
          $link,
          VERBOSE_DEBUG,
          'Updating reference table for Riksbanken reference rate.'
        );
        get_reference_rate($link);
        if (!is_reference_rate_updated($link)) {
          cl(
            $link,
            VERBOSE_ERROR,
            'Riksbanken reference rate could not be updated, will not remind'
          );
          die(1);
        }
      }

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
        'Searching for debts to remind'
      );

      # get all active debts with active status and not reminded yet
      $sql = '
        SELECT
          debts.id,
          debtors.email,
          debtors.email_bcc
        FROM
          invoicereminder_debts AS debts
          LEFT JOIN
            invoicereminder_contacts AS debtors ON debts.id_contacts_debtor = debtors.id
        WHERE
          debts.status='.dbres($link, DEBT_STATUS_ACTIVE).'
          AND
          debts.last_reminder <= timestampadd(day, -debts.reminder_days,now())
          AND (
            debts.day_of_month = 0
            OR
            DAYOFMONTH(NOW()) >= debts.day_of_month
          )
        ';

      cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
      $debts = db_query($link, $sql);
      if ($debts === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      # no debt found?
      if (!count($debts)) {
        # log it
        cl($link, VERBOSE_DEBUG, 'No unreminded debts found');
        break;
      }

      foreach ($debts as $debt) {

        $mail = compose_mail($link, $debt['id']);

        if (!$mail) {
          continue;
        }

        # log it
        cl($link, VERBOSE_DEBUG, 'Sending mail to: '.$mail['to'], $debt['id']);

        # try to send the mail
        if (!$config_opt['main']['dryrun']) {
          $mail_sent = mail(
            $mail['to'],
            $mail['subject'],
            $mail['body'],
            implode("\r\n", $mail['headers'])
          );
        } else {
          $mail_sent = true;
        }

        # did mail fail?
        if ($mail_sent === false) {
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
            'Failed sending mail to '.
              $mail['to'].' (bcc: '.
              $debt['email_bcc'].')',
            $debt['id']
          );

          # take next debt
          continue;
        }

        if (!$config_opt['main']['dryrun']) {
          # update last reminder on this debt
          $sql = '
            UPDATE
              invoicereminder_debts
            SET
              updated="'.dbres($link, date('Y-m-d H:i:s')).'",
              last_reminder="'.dbres($link, date('Y-m-d H:i:s')).'",
              mails_sent=mails_sent+1
            WHERE
              id="'.dbres($link, $debt['id']).'"
            ';
          cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
          $r = db_query($link, $sql);
          if ($r === false) {
            cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
            die(1);
          }
        }

        # log that mail has been sent
        cl($link, VERBOSE_INFO, 'Mail sent: '.$mail['to'], $debt['id']);
      }

      die(0);

    case 'remindreset': # to reset last reminded dates on active debts
      # log it
      cl(
        $link,
        VERBOSE_INFO,
        'Resetting all active debt reminder dates.'
      );

      $sql = '
        UPDATE
          invoicereminder_debts
        SET
          last_reminder="'.dbres($link, '1970-01-01 00:00:00').'"
        WHERE
          status="'.dbres($link, DEBT_STATUS_ACTIVE).'"';
      cl($link, VERBOSE_DEBUG_DEEP, 'SQL: '.$sql);
      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }
      die(0);

    case 'updatereference':
      if (is_reference_rate_updated($link)) {
        cl(
          $link,
          VERBOSE_DEBUG,
          'Riksbanken reference rate is already up to date.'
        );
        break;
      }
      cl(
        $link,
        VERBOSE_DEBUG,
        'Updating reference table for Riksbanken reference rate.'
      );
      get_reference_rate($link);
      break;
  }
?>
