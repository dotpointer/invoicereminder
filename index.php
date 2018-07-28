<?php
  # changelog
  # 2017-02-17 19:20:53 - initial version
  # 2017-02-18 00:04:08 - adding log
  # 2017-05-02 11:06:36 - bugfix, adding reminder cost to summary
  # 2017-12-09 20:53:00 - adding Riksbanken reference rate
  # 2018-02-13 18:37:00 - updating reference rate display
  # 2018-02-26 12:56:00 - adding total calculation for end of last year
  # 2018-03-20 20:02:06
  # 2018-07-28 16:13:32 - indentation change, tab to 2 spaces
  # 2018-07-28 17:01:00 - renaming from invoicenagger to invoicereminder

  require_once('include/functions.php');

  # parameters
  $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
  $address = isset($_REQUEST['address']) ? $_REQUEST['address'] : false;
  $amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : false;
  $city = isset($_REQUEST['city']) ? $_REQUEST['city'] : false;
  $collectioncost = isset($_REQUEST['collectioncost']) ? $_REQUEST['collectioncost'] : false;
  $day_of_month = isset($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : false;
  $duedate = isset($_REQUEST['duedate']) ? $_REQUEST['duedate'] : false;
  $email_bcc = isset($_REQUEST['email_bcc']) ? $_REQUEST['email_bcc'] : false;
  $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : false;
  $id_debtors = isset($_REQUEST['id_debtors']) ? $_REQUEST['id_debtors'] : false;
  $invoicedate = isset($_REQUEST['invoicedate']) ? $_REQUEST['invoicedate'] : false;
  $invoicenumber = isset($_REQUEST['invoicenumber']) ? $_REQUEST['invoicenumber'] : false;
  $last_reminder = isset($_REQUEST['last_reminder']) ? $_REQUEST['last_reminder'] : false;
  $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : false;
  $orgno = isset($_REQUEST['orgno']) ? $_REQUEST['orgno'] : false;
  $percentage = isset($_REQUEST['percentage']) ? $_REQUEST['percentage'] : false;

  $referencerate = isset($_REQUEST['$referencerate']) ? $_REQUEST['$referencerate'] : false;

  $remindercost = isset($_REQUEST['remindercost']) ? $_REQUEST['remindercost'] : false;
  $reminder_days = isset($_REQUEST['reminder_days']) ? $_REQUEST['reminder_days'] : false;
  $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : false;
  $template = isset($_REQUEST['template']) ? $_REQUEST['template'] : false;
  $view = isset($_REQUEST['view']) ? $_REQUEST['view'] : false;
  $zipcode = isset($_REQUEST['zipcode']) ? $_REQUEST['zipcode'] : false;

  switch ($action) {
    case 'insert_update_debtor':

      $iu = array(
        'address' => $address,
        'amount' => $amount,
        'city' => $city,
        'collectioncost' => $collectioncost,
        'day_of_month' => $day_of_month,
        'duedate' =>  $duedate,
        'email' => $email,
        'email_bcc' => $email_bcc,
        'invoicedate' => $invoicedate,
        'invoicenumber' => $invoicenumber,
        'last_reminder' => $last_reminder,
        'name' => $name,
        'orgno' => $orgno,
        'percentage' => $percentage,
        'remindercost' => $remindercost,
        'reminder_days' => $reminder_days,
        'status' => $status,
        'template' => $template,
        'updated' => date('Y-m-d H:i:s'),
        'zipcode' => $zipcode
      );

      # new debtor
      if (!$id_debtors) {

        $iu['created'] = date('Y-m-d H:i:s');
        $iu = dbpia($link, $iu);
        $sql = '
          INSERT INTO invoicereminder_debtors ('.
            implode(', ', array_keys($iu)).
          ') VALUES('.
            implode(', ', $iu).
          ')';
      # update debtor
      } else {
        $iu = dbpua($link, $iu);
        $sql = '
          UPDATE
            invoicereminder_debtors
          SET
            '.implode(', ', $iu).'
          WHERE
            id="'.dbres($link, $id_debtors).'"
          ';
      }

      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      header('Location: ?view=debtors');
      die();
  }

  # find out what view to prepare
  switch ($view) {
    default:

      # get reference rate, descending
      $sql = '
        SELECT
          *
        FROM
          invoicereminder_riksbank_reference_rate
        ORDER BY updated DESC
        ';
      $referencerate = db_query($link, $sql);

      # get debtors
      $sql = '
        SELECT
          *
        FROM
          invoicereminder_debtors
        ';
      $debtors = db_query($link, $sql);
      break;
    case 'edit_debtor':

      # find all template files
      $templatefiles = scandir(TEMPLATE_DIR);

      # filter so all text files are left
      $temp = array();
      foreach ($templatefiles as $templatefile) {
        if (substr(strtolower($templatefile), -4) !== '.txt') {
          continue;
        }
        $temp[] = $templatefile;
      }
      $templatefiles = $temp;

      # was there a debtor requested?
      if ($id_debtors) {
        # find the debtor
        $sql = '
          SELECT
            *
          FROM
            invoicereminder_debtors
          WHERE
            id="'.dbres($link, $id_debtors).'"
          ';
        $debtors = db_query($link, $sql);
        if ($debtors === false) {
          cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
          die(1);
        }

        # was there a debtor found?
        if (count($debtors)) {
          # then take that
          $debtor = reset($debtors);
        } else {
          $debtor = false;
        }

      } else {
        $debtor = false;
      }
      break;
    case 'log':
      $sql = '
        SELECT
          *
        FROM
          invoicereminder_log
        '.($id_debtors !== false ? 'WHERE id_debtors="'.dbres($link, $id_debtors).'"' : '').'
        ORDER BY
          created DESC
        LIMIT 25
        ';
      $log = db_query($link, $sql);
      if ($log === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }
      break;
    case 'referencerate':
      $sql = '
        SELECT
          *
        FROM
          invoicereminder_riksbank_reference_rate
        ORDER BY
          updated DESC
        ';
      $referencerate = db_query($link, $sql);
      break;
  }

?><!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <title>Fakturapåminnare</title>
</head>
<body>
  <a href="?view=">Påminnelser</a>
  <a href="?view=edit_debtor">Ny gäldenär</a>
  <a href="?view=log">Händelselogg</a>
  <a href="?view=referencerate">Referensränta</a>
<?php
  # find out what view to display
  switch ($view) {
    default:
?>
  <h1>Påminnelser</h1>
  <table border="1">
    <tr>
      <th>#</th>
      <th>Fakt-#</th>
      <th>Namn</th>
      <th>Fakt.bel.</th>
      <th>Inkasso<br>Påminnelse</th>
      <th>Ränta<br>Upplupet</th>
      <th>Totalt</th>
      <th>E-post</th>
      <th>Förfallodatum</th>
      <th>Status</th>
      <th>Sänt</th>
      <th>Intervall</th>
      <th>Dag</th>
      <th>Mall</th>
      <th>Hantera</th>
    </tr>
<?php
      # walk debtors
      foreach ($debtors as $debtor) {

        # start date and end date for interest calculation
        $date1 = new DateTime($debtor['duedate']);
        $date2 = new DateTime();

        # no need to remove one day here, as PHP does not add one
        $days_elapsed = $date2->diff($date1)->format("%a");

        # calculate interest for one day
        # $perday = (($debtor['amount']) * $debtor['percentage']) / 365;


        # get closest new year


        # calculate interest for all days that has elapsed
        $interest = 0;
        $interest_last_year = 0;
        $date_last_year_end = date('Y-m-d', strtotime("Last day of December", mktime(0, 0, 0, date('m'), date('d'), date('Y') - 1)));
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

          $interest_this_day = (($debtor['amount']) * ($debtor['percentage'] + $refrate)) / 365;

          $interest += $interest_this_day;

          # is this before or up to last years last day?
          if (strtotime($thisdate) <= strtotime($date_last_year_end)) {
            $interest_last_year += $interest_this_day;
          }

          # echo 'Ränta för '.$thisdate.' beräknas på '.$refdate.' '.$refrate.' - > '. ($debtor['percentage'] + ($refrate * 0.01))."\n";
        }

        # $interest = $perday * $days_elapsed;

        # summarize all costs
        $total = $debtor['amount'];
        $total += $debtor['collectioncost'];
        $total += $debtor['remindercost'];

        $total_last_year_end = $total;

        $total += $interest;
        $total_last_year_end += $interest_last_year;

?>
    <tr>
      <td><?php echo $debtor['id']; ?></td>
      <td><?php echo $debtor['invoicenumber']; ?></td>
      <td>
        <?php echo $debtor['name']; ?><br>
        <?php echo $debtor['address']; ?><br>
        <?php echo $debtor['zipcode']; ?> <?php echo $debtor['city']; ?><br>
        <?php echo $debtor['orgno']; ?>
      </td>
      <td><?php echo number_format($debtor['amount'], 2, ',', ','); ?> kr</td>
      <td>
        <?php echo number_format($debtor['collectioncost'], 2, ',', ','); ?> kr
        <br>
        <?php echo number_format($debtor['remindercost'], 2, ',', ','); ?> kr
      </td>
      <td>
        <?php echo $debtor['percentage'] * 100; ?>%<br>
        <?php echo number_format($interest, 2, ',', ','); ?> kr
      </td>
      <td>
        <?php echo number_format($total, 2, ',', ',') ?> kr
        <br>
        (<?php echo $date_last_year_end ?>: <?php echo number_format($total_last_year_end, 2, ',', ',') ?> kr)
      </td>
      <td>
        <?php echo $debtor['email']; ?><br>
        (<?php echo $debtor['email_bcc']; ?>)
      </td>
      <td><?php echo $debtor['duedate']; ?></td>
      <td><?php
        switch ($debtor['status']) {
          case DEBTOR_STATUS_ACTIVE:
            ?>Aktiv<?php
            break;
          case DEBTOR_STATUS_INACTIVE:
            ?>Inaktiv<?php
            break;
          case DEBTOR_STATUS_ERROR:
            ?>Fel<?php
            break;
          default:
            echo $debtor['status'];
            break;
        }
      ?></td>
      <td><?php echo $debtor['mails_sent']; ?> st</td>
      <td><?php echo $debtor['reminder_days']; ?></td>
      <td><?php echo $debtor['day_of_month']; ?></td>
      <td><?php echo $debtor['template']; ?></td>
      <td>
        <a href="?view=edit_debtor&amp;id_debtors=<?php echo $debtor['id'] ?>">Redigera</a>
        <br>
        <a href="?view=log&amp;id_debtors=<?php echo $debtor['id'] ?>">Logg</a>
      </td>
    </tr>
<?php
      } # walk debtors
?>
  </table>
<?php
      break;
    case 'edit_debtor':
?>
  <h1>Redigera gäldenär</h1>
  <form action="?" method="post">
    <fieldset>

      <input type="hidden" name="action" value="insert_update_debtor">

      <label>#:</label><br>
      <span class="value"><?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : 'Ny gäldenär' ?></span>
      <input type="hidden" name="id_debtors" value="<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>">
      <br>

      <label>Fakturanummer:</label><br>
      <input type="text" name="invoicenumber" value="<?php echo is_array($debtor) && isset($debtor['invoicenumber']) ? $debtor['invoicenumber'] : '' ?>">
      <br>

      <label>Namn:</label><br>
      <input type="text" name="name" value="<?php echo is_array($debtor) && isset($debtor['name']) ? $debtor['name'] : '' ?>">
      <br>

      <label>Adress:</label><br>
      <input type="text" name="address" value="<?php echo is_array($debtor) && isset($debtor['address']) ? $debtor['address'] : '' ?>">
      <br>

      <label>Postnummer:</label><br>
      <input type="text" name="zipcode" value="<?php echo is_array($debtor) && isset($debtor['zipcode']) ? $debtor['zipcode'] : '' ?>">
      <br>


      <label>Postort:</label><br>
      <input type="text" name="city" value="<?php echo is_array($debtor) && isset($debtor['city']) ? $debtor['city'] : '' ?>">
      <br>

      <label>Organisations-/personnummer:</label><br>
      <input type="text" name="orgno" value="<?php echo is_array($debtor) && isset($debtor['orgno']) ? $debtor['orgno'] : '' ?>">
      <br>


      <label>Fakturabelopp:</label><br>
      <input type="text" name="amount" value="<?php echo is_array($debtor) && isset($debtor['amount']) ? $debtor['amount'] : '' ?>">
      <br>

      <label>Inkassokostnad:</label><br>
      <input type="text" name="collectioncost" value="<?php echo is_array($debtor) && isset($debtor['collectioncost']) ? $debtor['collectioncost'] : '' ?>">
      <br>

      <label>Påminnelseavgift:</label><br>
      <input type="text" name="remindercost" value="<?php echo is_array($debtor) && isset($debtor['remindercost']) ? $debtor['remindercost'] : '' ?>">
      <br>

      <label>Procent:</label><br>
      <input type="text" name="percentage" value="<?php echo is_array($debtor) && isset($debtor['percentage']) ? $debtor['percentage'] : '' ?>">
      <br>

      <label>E-post, gäldenär:</label><br>
      <input type="text" name="email" value="<?php echo is_array($debtor) && isset($debtor['email']) ? $debtor['email'] : '' ?>">
      <br>

      <label>E-post, dold kopia:</label><br>
      <input type="text" name="email_bcc" value="<?php echo is_array($debtor) && isset($debtor['email_bcc']) ? $debtor['email_bcc'] : '' ?>">
      <br>

      <label>Fakturadatum:</label><br>
      <input type="text" name="invoicedate" value="<?php echo is_array($debtor) && isset($debtor['invoicedate']) ? $debtor['invoicedate'] : '' ?>">
      <br>

      <label>Förfallodag:</label><br>
      <input type="text" name="duedate" value="<?php echo is_array($debtor) && isset($debtor['duedate']) ? $debtor['duedate'] : '' ?>">
      <br>

      <label>Dagar mellan påminnelse:</label><br>
      <input type="number" name="reminder_days" value="<?php echo is_array($debtor) && isset($debtor['reminder_days']) ? $debtor['reminder_days'] : '' ?>">
      <br>

      <label>Dag i månaden tidigast:</label><br>
      <input type="number" min="1" max="31" name="day_of_month" value="<?php echo is_array($debtor) && isset($debtor['day_of_month']) ? $debtor['day_of_month'] : '' ?>">
      <br>

      <label>Mall:</label><br>
      <select name="template">
        <?php foreach ($templatefiles as $templatefile) { ?>
        <option value="<?php echo $templatefile ?>"<?php echo is_array($debtor) && isset($debtor['template']) && $debtor['template'] === $templatefile ? ' selected' : '' ?>><?php echo $templatefile ?></option>
        <?php } ?>
      </select>
      <br>

      <label>Status:</label><br>
      <select name="status">
        <option value="<?php echo DEBTOR_STATUS_ACTIVE ?>"<?php echo is_array($debtor) && isset($debtor['status']) && (int)$debtor['status'] === DEBTOR_STATUS_ACTIVE ? ' selected' : '' ?>>Aktiv</option>
        <option value="<?php echo DEBTOR_STATUS_INACTIVE ?>"<?php echo is_array($debtor) && isset($debtor['status']) && (int)$debtor['status'] === DEBTOR_STATUS_INACTIVE ? ' selected' : '' ?>>Inaktiv</option>
        <option value="<?php echo DEBTOR_STATUS_ERROR ?>"<?php echo is_array($debtor) && isset($debtor['status']) && (int)$debtor['status'] === DEBTOR_STATUS_ERROR ? ' selected' : '' ?>>Fel</option>
      </select>
      <br>


      <input type="submit" name="submit_edit_debtor" value="Spara">
      <br>

    </fieldset>
  </form>
<?php
      break;
    case 'log':
?>
  <table border="1">
    <tr>
      <th>Datum</th>
      <th>Typ</th>
      <th>Text</th>
    </tr>
<?php
      foreach ($log as $logmessage) {
?>
    <tr>
      <td><?php echo $logmessage['created'] ?></td>
      <td><?php
        switch ($logmessage['type']) {
          default:
            echo $logmessage['type'];
            break;
          case VERBOSE_OFF:		# no info at all
            ?>Av<?php
            break;
          case VERBOSE_ERROR:		# only errors
            ?>Fel<?php
            break;
          case VERBOSE_INFO:		# above and things that changes
            ?>Info<?php
            break;
          case VERBOSE_DEBUG:		# above and verbose info
          case VERBOSE_DEBUG_DEEP:
            ?>Debug<?php
            break;

        }
      ?></td>
      <td><?php echo $logmessage['message'] ?></td>
    </tr>
<?php
      }
?>
  </table>
<?php

      break;
    case 'referencerate':
?>
  <table border="1">
    <tr>
      <th>Datum</th>
      <th>Ränta</th>
    </tr>
<?php
      foreach ($referencerate as $row) {
?>
    <tr>
      <td><?php echo $row['updated'] ?></td>
      <td><?php echo $row['rate'] ?></td>
    </tr>
<?php
      }
?>
  </table>
<?php
/*
  <h1>Riksbankens referensränta</h1>
  <form action="?" method="post">
    <fieldset>
      <label>Gäller från (YYYY-MM-DD):</label>
      <input type="text" name="updated">
      <br>

      <label>Referensränta:</label>
      <input type="text" name="referencerate">
      <br>

      <input type="submit" name="submit_edit_debtor" value="Spara">
      <br>
    </fieldset>
  </form>
*/
    break;

  }
?>
</body>
</html>
