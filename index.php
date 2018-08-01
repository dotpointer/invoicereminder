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
  # 2018-07-30 00:00:00 - adding balance
  # 2018-07-31 00:00:00 - adding balance
  # 2018-08-01 18:43:00 - adding balance

  require_once('include/functions.php');

  # parameters
  $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
  $address = isset($_REQUEST['address']) ? $_REQUEST['address'] : false;
  $amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : false;
  $city = isset($_REQUEST['city']) ? $_REQUEST['city'] : false;
  $collectioncost = isset($_REQUEST['collectioncost']) ? $_REQUEST['collectioncost'] : false;
  $cost = isset($_REQUEST['cost']) ? $_REQUEST['cost'] : false;
  $day_of_month = isset($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : false;
  $duedate = isset($_REQUEST['duedate']) ? $_REQUEST['duedate'] : false;
  $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : false;
  $email_bcc = isset($_REQUEST['email_bcc']) ? $_REQUEST['email_bcc'] : false;
  $happened = isset($_REQUEST['happened']) ? $_REQUEST['happened'] : false;
  $id_balance = isset($_REQUEST['id_balance']) ? $_REQUEST['id_balance'] : false;
  $id_debtors = isset($_REQUEST['id_debtors']) ? $_REQUEST['id_debtors'] : false;
  $invoicedate = isset($_REQUEST['invoicedate']) ? $_REQUEST['invoicedate'] : false;
  $invoicenumber = isset($_REQUEST['invoicenumber']) ? $_REQUEST['invoicenumber'] : false;
  $last_reminder = isset($_REQUEST['last_reminder']) ? $_REQUEST['last_reminder'] : false;
  $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : false;
  $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : false;
  $onlychanges = isset($_REQUEST['onlychanges']) ? $_REQUEST['onlychanges'] : false;
  $orgno = isset($_REQUEST['orgno']) ? $_REQUEST['orgno'] : false;
  $payment = isset($_REQUEST['payment']) ? $_REQUEST['payment'] : false;
  $percentage = isset($_REQUEST['percentage']) ? $_REQUEST['percentage'] : false;
  $referencerate = isset($_REQUEST['$referencerate']) ? $_REQUEST['$referencerate'] : false;
  $reminder_days = isset($_REQUEST['reminder_days']) ? $_REQUEST['reminder_days'] : false;
  $remindercost = isset($_REQUEST['remindercost']) ? $_REQUEST['remindercost'] : false;
  $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : false;
  $template = isset($_REQUEST['template']) ? $_REQUEST['template'] : false;
  $view = isset($_REQUEST['view']) ? $_REQUEST['view'] : false;
  $zipcode = isset($_REQUEST['zipcode']) ? $_REQUEST['zipcode'] : false;

  $dateto = isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : false;


  switch ($action) {
    case 'insert_update_balance':
      $iu = array(
          'amount' => $amount,
          'cost' => $cost,
          'payment' => $payment,
          'happened' => $happened,
          'message' => $message
      );

      # new balance
      if (!$id_balance) {
        $iu['created'] = date('Y-m-d H:i:s');
        $iu['id_debtors'] = $id_debtors;
        $iu = dbpia($link, $iu);
        $sql = '
          INSERT INTO invoicereminder_balance ('.
            implode(', ', array_keys($iu)).
          ') VALUES('.
            implode(', ', $iu).
          ')';
      # update balance
      } else {
        $iu['updated'] = date('Y-m-d H:i:s');
        $iu = dbpua($link, $iu);
        $sql = '
          UPDATE
            invoicereminder_balance
          SET
            '.implode(', ', $iu).'
          WHERE
            id="'.dbres($link, $id_balance).'"
          ';
      }
      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      header('Location: ?view=balance&id_debtors='.$id_debtors);
      die();
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

    case 'balance':
      # get the debtor
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
      }

      $sql = '
        SELECT
          *
        FROM
          invoicereminder_balance
        WHERE id_debtors="'.dbres($link, $id_debtors).'"
        ORDER BY
          happened ASC
        ';
      $balance = db_query($link, $sql);
      if ($balance === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }
      break;

    case 'edit_balance':

      # was there a balance requested?
      if ($id_balance) {
        # find the balance
        $sql = '
          SELECT
            *
          FROM
            invoicereminder_balance
          WHERE
            id="'.dbres($link, $id_balance).'"
          ';
        $balance = db_query($link, $sql);
        if ($balance === false) {
          cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
          die(1);
        }

        # was there a balance found?
        if (count($balance)) {
          # then take that
          $balance = reset($balance);
        } else {
          $balance = false;
        }

      } else {
        $balance = false;
      }

      $id_debtors = is_array($balance) ? $balance['id_debtors'] : $id_debtors;

      # get the debtor
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
        echo 'Saknar id_debtors';
        die();
        $debtor = false;
      }
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
    case 'history':

      # get the debtor
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
      }

      $balance_history = balance_history($link, $id_debtors);
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
  <link rel="stylesheet" href="include/style.css" />
  <title>Fakturapåminnare</title>
</head>
<body>
<h1>Fakturapåminnare</h1>
<ul class="menu">
  <li><a href="?view=">Påminnelser</a></li>
  <li><a href="?view=edit_debtor">Ny gäldenär</a></li>
  <li><a href="?view=log">Händelselogg</a></li>
  <li><a href="?view=referencerate">Referensränta</a></li>
</ul>
<?php
  # find out what view to display
  switch ($view) {
    default:
?>
  <h2>Påminnelser</h2>
  <table border="1">
    <tr>
      <th>#</th>
      <th>Fakt-#</th>
      <th>Namn</th>
      <th>Fakt.bel.</th>
      <th>Kostnader</th>
      <th>Ränta (%)<br>Upplupet</th>
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

        $balance_history = balance_history($link, $debtor['id']);
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
      <td class="amount">
        <?php echo money($debtor['amount']); ?> kr
      </td>
      <td class="amount">
<?php
    foreach ($balance_history['history'] as $bk => $b) {
      if ($b['cost_this_day'] === 0) continue;
      if ($bk) {
        ?><br><?php
      }
      echo $b['message'].' '.money($b['cost_this_day']); ?> kr<?php
    }
?>
      </td>
      <td class="percentage">
        <?php echo $debtor['percentage'] * 100; ?>%<br>
<?php
      $bend = end($balance_history['history']);
      echo money($bend['interest_accrued']); ?> kr
      </td>
      <td class="amount">
        <?php echo money($bend['amount_accrued'] + $bend['interest_accrued'] + $bend['cost_accrued']) ?> kr
        <br>
        (<?php echo $balance_history['special']['date_last_year_end'] ?>: <?php echo money($balance_history['special']['total_last_year_end']) ?> kr)
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
        <a href="?view=log&amp;id_debtors=<?php echo $debtor['id'] ?>">Maillogg</a>
        <br>
        <a href="?view=balance&amp;id_debtors=<?php echo $debtor['id'] ?>">Balans</a>
        <br>
        <a href="?view=history&amp;id_debtors=<?php echo $debtor['id'] ?>">Historik</a>
      </td>
    </tr>
<?php
      } # walk debtors
?>
  </table>
<?php
      break;
    case 'balance':
?>
  <h2>Balans</h2>
  <p>Gäldenär: <?php echo $debtor['name']?> (#<?php echo $debtor['id'] ?>).
  <p>
    Denna sida listar införda balansförändringar.
  </p>
  <p>
    <a href="?view=edit_balance&amp;id_debtors=<?php echo $id_debtors?>">Ny balansrad</a> /
    <a href="?view=history&amp;id_debtors=<?php echo $id_debtors?>">Skuldhistorik</a>
  </p>
  <table border="1">
    <tr>
      <th>Datum</th>
      <th>Skuldbelopp</th>
      <th>Kostnad</th>
      <th>Betalt</th>
      <th>Meddelande</th>
      <th>Hantera</th>
    </tr>
<?php
      # walk debtors
      foreach ($balance as $k => $row) {
?>
    <tr>
      <td><?php echo date('Y-m-d', strtotime($row['happened'])) ?></td>
      <td class="amount">
        <?php echo money($row['amount']) ?>
      </td>
      <td class="amount">
        <?php echo money($row['cost']) ?>
      </td>
      <td class="amount">
        <?php echo money($row['payment']) ?>
      </td>
      <td><?php echo $row['message'] ?></td>
      <td>
        <a href="?view=edit_balance&amp;id_balance=<?php echo $row['id']?>&amp;id_debtors=<?php echo $id_debtors ?>">Redigera</a>
      </td>
    </tr>
<?php
      }
?>
  </table>
<?php
      break;
    case 'history':
?>
  <h2>Skuldhistorik</h2>
  <p>Gäldenär: <?php echo $debtor['name']?> (#<?php echo $debtor['id'] ?>).
  <p>
    Denna sida listar skuldhistorik.
  </p>
  <p>
    Visa:
    <a href="?view=history&amp;id_debtors=<?=$id_debtors?>&amp;onlychanges=1">Förändringar</a>
    /
    <a href="?view=history&amp;id_debtors=<?=$id_debtors?>&amp;onlychanges=0">Allt</a>
    /
    <a href="?view=balance&amp;id_debtors=<?=$id_debtors?>">Balans</a>
  </p>
  <table border="1">
    <tr>
      <th>Dagnr</th>
      <th>Datum</th>
      <th>Skuldbelopp</th>
      <th>Upplupen ränta</th>
      <th>Kostnad</th>
      <th>Betalningar</th>
      <th>Ränta (%) (ref.ränta %)</th>
      <th>Total</th>
      <th>Meddelande</th>
      <th>Mail</th>
      <th>Hantera</th>
    </tr>
<?php
      # walk debtors
      foreach ($balance_history['history'] as $k => $row) {

        if ($onlychanges == '1') {
          # is this a change row or last row
          if (
            !$row['changesthisday'] &&
            $k !== 0 &&
            $k !== count($balance_history['history']) - 1
          ) {
            continue;
          }
        }
?>
    <tr>
      <td><?php echo $k + 1 ?></td>
      <td><?php echo $row['date'] ?></td>
      <td class="amount">
        <?php echo money($row['amount_accrued']) ?>
<?php
        $changes = array();
        if ($row['amount_this_day'] !== 0) {
          $changes[] = ($row['amount_this_day'] > 0 ? '+' : '').money($row['amount_this_day']);
        }

        if ($row['amount_paid_this_day'] !== 0) {
          $changes[] = (-$row['amount_paid_this_day'] > 0 ? '+' : '').money(-$row['amount_paid_this_day']);
        }
        # any change this day?
        if (count($changes)) {
?>
          (<?php echo implode(', ', $changes) ?>)
<?php
        }
?>
      </td>
      <td class="amount">
        <?php echo money($row['interest_accrued']) ?>
<?php
        $changes = array();
        if ($row['interest_this_day'] !== 0) {
          $changes[] = ($row['interest_this_day'] > 0 ? '+' : '').money($row['interest_this_day']);
        }

        if ($row['interest_paid_this_day'] !== 0) {
          $changes[] = (-$row['interest_paid_this_day'] > 0 ? '+' : '').money(-$row['interest_paid_this_day']);
        }
        # any change this day?
        if (count($changes)) {
?>
          (<?php echo implode(', ', $changes) ?>)
<?php
        }
?>
      </td>
      <td class="amount">
        <?php echo money($row['cost_accrued']) ?>
<?php
        $changes = array();
        if ($row['cost_this_day'] !== 0) {
          $changes[] = ($row['cost_this_day'] > 0 ? '+' : '').money($row['cost_this_day']);
        }

        if ($row['cost_paid_this_day'] !== 0) {
          $changes[] = (-$row['cost_paid_this_day'] > 0 ? '+' : '').money(-$row['cost_paid_this_day']);
        }
        # any change this day?
        if (count($changes)) {
?>
          (<?php echo implode(', ', $changes) ?>)
<?php
        }
?>
      </td>
      <td class="amount">
        <?php echo money($row['payment_accrued']) ?>
<?php
        $changes = array();
        if ($row['payment_this_day'] !== 0) {
          $changes[] = ($row['payment_this_day'] > 0 ? '+' : '').money($row['payment_this_day']);
        }

        # any change this day?
        if (count($changes)) {
?>
          (<?php echo implode(', ', $changes) ?>)
<?php
        }
?>
      </td>
      <td class="percentage"><?php echo percentage(($row['rate']+$row['refrate'])*100).' ('.percentage($row['refrate']*100).')' ?></td>
      <td class="amount"><?php echo money($row['total']) ?></td>
      <td><?php echo $row['message'] ?></td>
      <td><?php echo $row['mails_sent'] ?></td>
      <td>
        <a href="?view=mail&amp;id_debtors=<?php echo $debtor['id'] ?>&amp;dateto=<?=$row['date']?>">Mail</a>
      </td>
    </tr>
<?php
      }
?>
  </table>
<?php
      break;
    case 'edit_balance':
?>
  <h2>Redigera balansrad</h2>
  <form action="?" method="post">
    <fieldset>
      <input type="hidden" name="action" value="insert_update_balance">
      <input type="hidden" name="id_balance" value="<?php echo $id_balance ?>">

      <label>#:</label><br>
      <span class="value"><?php echo is_array($balance) && isset($balance['id']) ? $balance['id'] : 'Ny balansrad' ?></span>
      <input type="hidden" name="id_balance" value="<?php echo is_array($balance) && isset($balance['id']) ? $balance['id'] : '' ?>">
      <br>

      <label>Gäldenär:</label><br>
      <span class="value"><?php echo is_array($debtor) && isset($debtor['name']) ? $debtor['name'] : 'Ingen' ?> (#<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>)</span>
      <input type="hidden" name="id_debtors" value="<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>">
      <br>

      <label>Skuldbelopp, förändring:</label><br>
      <input type="text" name="amount" value="<?php echo is_array($balance) && isset($balance['amount']) ? $balance['amount'] : '' ?>"> (negativt = minskad skuld)
      <span class="description">
        Fyll i eventuell förändring i skuldbelopp, positivt belopp.
      </span>
      <br>

      <label>Kostnad, förändring:</label><br>
      <input type="text" name="cost" value="<?php echo is_array($balance) && isset($balance['cost']) ? $balance['cost'] : '' ?>">
      <span class="description">
        Fyll i eventuell förändring i skuldbelopp, positivt belopp.
      </span>
      <br>

      <label>Betalning, förändring:</label><br>
      <input type="text" name="payment" value="<?php echo is_array($balance) && isset($balance['payment']) ? $balance['payment'] : '' ?>">
      <span class="description">
        Fyll i eventuell betalning, positivt belopp.
      </span>
      <br>

      <label>Förfallodag:</label><br>
      <input type="text" name="happened" value="<?php echo is_array($balance) && isset($balance['happened']) ? $balance['happened'] : '' ?>">
      <br>

      <label>Meddelande/förklaring:</label><br>
      <input type="text" name="message" value="<?php echo is_array($balance) && isset($balance['message']) ? $balance['message'] : '' ?>">
      <br>

      <input type="submit" name="submit_edit_balance" value="Spara">
      <br>
    </fieldset>
  </form>
<?php
      break;
    case 'edit_debtor':
?>
  <h2>Redigera gäldenär</h2>
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

      <!--
      <label>Fakturabelopp:</label><br>
      <input type="text" name="amount" value="<?php echo is_array($debtor) && isset($debtor['amount']) ? $debtor['amount'] : '' ?>">
      <br>

      <label>Inkassokostnad:</label><br>
      <input type="text" name="collectioncost" value="<?php echo is_array($debtor) && isset($debtor['collectioncost']) ? $debtor['collectioncost'] : '' ?>">
      <br>

      <label>Påminnelseavgift:</label><br>
      <input type="text" name="remindercost" value="<?php echo is_array($debtor) && isset($debtor['remindercost']) ? $debtor['remindercost'] : '' ?>">
      <br>
      -->

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
  <h2>Maillogg</h2>
  <p>Denna sida visar när mail skickats till mottagare.</p>
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
    case 'mail':
      $mail = compose_mail($link, $id_debtors);
?>
<h1>Mail</h1>
<p>Detta är ett mail så som det skulle sett ut om det avsändes <?php echo $dateto?>.</p>
<pre>
<?php
      echo "\n".
        str_repeat('-', 80)."\n".
        implode(
          str_repeat('-', 80)."\n",
          array(
            'To: '.$mail['to']."\n".implode("\n", $mail['headers'])."\n",
            $mail['subject']."\n",
            $mail['body']
          )
        )."\n".
        str_repeat('-', 80)."\n";
?>
</pre>
<?php
      break;
    case 'referencerate':
?>
  <h2>Referensränta</h2>
  <p>Denna sida visar Riksbankens referensränta.</p>
  <table border="1">
    <tr>
      <th>Datum</th>
      <th>Ränta (%)</th>
    </tr>
<?php
      foreach ($referencerate as $row) {
?>
    <tr>
      <td><?php echo $row['updated'] ?></td>
      <td class="percentage"><?php echo percentage($row['rate']*100) ?></td>
    </tr>
<?php
      }
?>
  </table>
<?php
/*
  <h2>Riksbankens referensränta</h2>
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
