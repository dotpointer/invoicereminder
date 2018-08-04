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
  # 2018-08-04 13:38:00 - translations

  require_once('include/functions.php');
  start_translations();

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
  <title><?php echo t('Invoice reminder'); ?></title>
</head>
<body>
<h1><?php echo t('Invoice reminder'); ?></h1>
<ul class="menu">
  <li><a href="?view="><?php echo t('Reminders'); ?></a></li>
  <li><a href="?view=edit_debtor"><?php echo t('New debtor'); ?></a></li>
  <li><a href="?view=log"><?php echo t('Event log'); ?></a></li>
  <li><a href="?view=referencerate"><?php echo t('Reference rate'); ?></a></li>
</ul>
<?php
  # find out what view to display
  switch ($view) {
    default:
?>
  <h2><?php echo t('Reminders'); ?></h2>
  <table border="1">
    <tr>
      <th>#</th>
      <th><?php echo t('Invoice-#'); ?></th>
      <th><?php echo t('Name'); ?></th>
      <th><?php echo t('Invoice amount'); ?></th>
      <th><?php echo t('Costs'); ?></th>
      <th><?php echo t('Interest'); ?> (%)<br><?php echo t('Accrued'); ?></th>
      <th><?php echo t('Total'); ?></th>
      <th><?php echo t('E-mail'); ?></th>
      <th><?php echo t('Due date'); ?></th>
      <th><?php echo t('Status'); ?></th>
      <th><?php echo t('Sent'); ?></th>
      <th><?php echo t('Interval'); ?></th>
      <th><?php echo t('Date'); ?></th>
      <th><?php echo t('Template'); ?></th>
      <th><?php echo t('Manage'); ?></th>
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
            echo t('Active');
            break;
          case DEBTOR_STATUS_INACTIVE:
            echo t('Inactive');
            break;
          case DEBTOR_STATUS_ERROR:
            echo t('Error');
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
        <a href="?view=edit_debtor&amp;id_debtors=<?php echo $debtor['id'] ?>"><?php echo t('Edit'); ?></a>
        <br>
        <a href="?view=log&amp;id_debtors=<?php echo $debtor['id'] ?>"><?php echo t('E-mail log'); ?></a>
        <br>
        <a href="?view=balance&amp;id_debtors=<?php echo $debtor['id'] ?>"><?php echo t('Balance'); ?></a>
        <br>
        <a href="?view=history&amp;id_debtors=<?php echo $debtor['id'] ?>"><?php echo t('History'); ?></a>
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
  <h2><?php echo t('Balance'); ?></h2>
  <p><?php echo t('Debtor'); ?>: <?php echo $debtor['name']?> (#<?php echo $debtor['id'] ?>).
  <p>
    <?php echo t('This page shows balance changes.'); ?>
  </p>
  <p>
    <a href="?view=edit_balance&amp;id_debtors=<?php echo $id_debtors?>"><?php echo t('New row'); ?></a> /
    <a href="?view=history&amp;id_debtors=<?php echo $id_debtors?>"><?php echo t('Debt history'); ?></a>
  </p>
  <table border="1">
    <tr>
      <th><?php echo t('Date'); ?></th>
      <th><?php echo t('Debt amount'); ?></th>
      <th><?php echo t('Cost'); ?></th>
      <th><?php echo t('Payment'); ?></th>
      <th><?php echo t('Message'); ?></th>
      <th><?php echo t('Manage'); ?></th>
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
  <h2><?php echo t('Debt history'); ?></h2>
  <p><?php echo t('Debtor'); ?>: <?php echo $debtor['name']?> (#<?php echo $debtor['id'] ?>).
  <p>
    <?php echo t('This page shows debt history.'); ?>
  </p>
  <p>
    Visa:
    <a href="?view=history&amp;id_debtors=<?=$id_debtors?>&amp;onlychanges=1"><?php echo t('Changes'); ?></a>
    /
    <a href="?view=history&amp;id_debtors=<?=$id_debtors?>&amp;onlychanges=0"><?php echo t('Everything'); ?></a>
    /
    <a href="?view=balance&amp;id_debtors=<?=$id_debtors?>"><?php echo t('Balance'); ?></a>
  </p>
  <table border="1">
    <tr>
      <th><?php echo t('Day-#'); ?></th>
      <th><?php echo t('Date'); ?></th>
      <th><?php echo t('Debt amount'); ?></th>
      <th><?php echo t('Accrued interest'); ?></th>
      <th><?php echo t('Cost'); ?></th>
      <th><?php echo t('Payments'); ?></th>
      <th><?php echo t('Rate'); ?> (%) (<?php echo t('Reference rate'); ?> %)</th>
      <th><?php echo t('Total'); ?></th>
      <th><?php echo t('Message'); ?></th>
      <th><?php echo t('Mail'); ?></th>
      <th><?php echo t('Manage'); ?></th>
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
        <a href="?view=mail&amp;id_debtors=<?php echo $debtor['id'] ?>&amp;dateto=<?=$row['date']?>"><?php echo t('E-mail'); ?></a>
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
  <h2><?php echo t('Edit balance row'); ?></h2>
  <form action="?" method="post">
    <fieldset>
      <input type="hidden" name="action" value="insert_update_balance">
      <input type="hidden" name="id_balance" value="<?php echo $id_balance ?>">

      <label>#:</label><br>
      <span class="value"><?php echo is_array($balance) && isset($balance['id']) ? $balance['id'] : 'Ny balansrad' ?></span>
      <input type="hidden" name="id_balance" value="<?php echo is_array($balance) && isset($balance['id']) ? $balance['id'] : '' ?>">
      <br>

      <label><?php echo t('Debtor'); ?>:</label><br>
      <span class="value"><?php echo is_array($debtor) && isset($debtor['name']) ? $debtor['name'] : 'Ingen' ?> (#<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>)</span>
      <input type="hidden" name="id_debtors" value="<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>">
      <br>

      <label><?php echo t('Debt amount, change'); ?>:</label><br>
      <input type="text" name="amount" value="<?php echo is_array($balance) && isset($balance['amount']) ? $balance['amount'] : '' ?>"> (<?php echo t('negative = reduced debt'); ?>)
      <span class="description">
        <?php echo t('Fill in eventual change in debt amount, positive amount.'); ?>
      </span>
      <br>

      <label><?php echo t('Cost, change'); ?>:</label><br>
      <input type="text" name="cost" value="<?php echo is_array($balance) && isset($balance['cost']) ? $balance['cost'] : '' ?>">
      <span class="description">
        <?php echo t('Fill in eventual change in cost, positive amount.'); ?>
      </span>
      <br>

      <label><?php echo t('Payment, change'); ?>:</label><br>
      <input type="text" name="payment" value="<?php echo is_array($balance) && isset($balance['payment']) ? $balance['payment'] : '' ?>">
      <span class="description">
        <?php echo t('Fill in eventual payment, positive amount.'); ?>
      </span>
      <br>

      <label><?php echo t('Due date'); ?>:</label><br>
      <input type="text" name="happened" value="<?php echo is_array($balance) && isset($balance['happened']) ? $balance['happened'] : '' ?>">
      <br>

      <label><?php echo t('Message/explanation'); ?>:</label><br>
      <input type="text" name="message" value="<?php echo is_array($balance) && isset($balance['message']) ? $balance['message'] : '' ?>">
      <br>

      <input type="submit" name="submit_edit_balance" value="<?php echo t('Save'); ?>">
      <br>
    </fieldset>
  </form>
<?php
      break;
    case 'edit_debtor':
?>
  <h2><?php echo t('Edit debtor'); ?></h2>
  <form action="?" method="post">
    <fieldset>

      <input type="hidden" name="action" value="insert_update_debtor">

      <label>#:</label><br>
      <span class="value"><?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : 'Ny gäldenär' ?></span>
      <input type="hidden" name="id_debtors" value="<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>">
      <br>

      <label><?php echo t('Invoice number'); ?>:</label><br>
      <input type="text" name="invoicenumber" value="<?php echo is_array($debtor) && isset($debtor['invoicenumber']) ? $debtor['invoicenumber'] : '' ?>">
      <br>

      <label><?php echo t('Name'); ?>:</label><br>
      <input type="text" name="name" value="<?php echo is_array($debtor) && isset($debtor['name']) ? $debtor['name'] : '' ?>">
      <br>

      <label><?php echo t('Address'); ?>:</label><br>
      <input type="text" name="address" value="<?php echo is_array($debtor) && isset($debtor['address']) ? $debtor['address'] : '' ?>">
      <br>

      <label><?php echo t('Postal code'); ?>:</label><br>
      <input type="text" name="zipcode" value="<?php echo is_array($debtor) && isset($debtor['zipcode']) ? $debtor['zipcode'] : '' ?>">
      <br>


      <label><?php echo t('City'); ?>:</label><br>
      <input type="text" name="city" value="<?php echo is_array($debtor) && isset($debtor['city']) ? $debtor['city'] : '' ?>">
      <br>

      <label><?php echo t('Organisation-/social security number'); ?>:</label><br>
      <input type="text" name="orgno" value="<?php echo is_array($debtor) && isset($debtor['orgno']) ? $debtor['orgno'] : '' ?>">
      <br>

      <label><?php echo t('Interest percentage'); ?>:</label><br>
      <input type="text" name="percentage" value="<?php echo is_array($debtor) && isset($debtor['percentage']) ? $debtor['percentage'] : '' ?>">
      <br>

      <label><?php echo t('E-mail, debtor'); ?>:</label><br>
      <input type="text" name="email" value="<?php echo is_array($debtor) && isset($debtor['email']) ? $debtor['email'] : '' ?>">
      <br>

      <label><?php echo t('E-mail, hidden carbon copy'); ?>:</label><br>
      <input type="text" name="email_bcc" value="<?php echo is_array($debtor) && isset($debtor['email_bcc']) ? $debtor['email_bcc'] : '' ?>">
      <br>

      <label><?php echo t('Invoice date'); ?>:</label><br>
      <input type="text" name="invoicedate" value="<?php echo is_array($debtor) && isset($debtor['invoicedate']) ? $debtor['invoicedate'] : '' ?>">
      <br>

      <label><?php echo t('Due date'); ?>:</label><br>
      <input type="text" name="duedate" value="<?php echo is_array($debtor) && isset($debtor['duedate']) ? $debtor['duedate'] : '' ?>">
      <br>

      <label><?php echo t('Days between reminders'); ?>:</label><br>
      <input type="number" name="reminder_days" value="<?php echo is_array($debtor) && isset($debtor['reminder_days']) ? $debtor['reminder_days'] : '' ?>">
      <br>

      <label><?php echo t('Day in month, earliest'); ?>:</label><br>
      <input type="number" min="1" max="31" name="day_of_month" value="<?php echo is_array($debtor) && isset($debtor['day_of_month']) ? $debtor['day_of_month'] : '' ?>">
      <br>

      <label><?php echo t('Template'); ?>:</label><br>
      <select name="template">
        <?php foreach ($templatefiles as $templatefile) { ?>
        <option value="<?php echo $templatefile ?>"<?php echo is_array($debtor) && isset($debtor['template']) && $debtor['template'] === $templatefile ? ' selected' : '' ?>><?php echo $templatefile ?></option>
        <?php } ?>
      </select>
      <br>

      <label><?php echo t('Status'); ?>:</label><br>
      <select name="status">
        <option value="<?php echo DEBTOR_STATUS_ACTIVE ?>"<?php echo is_array($debtor) && isset($debtor['status']) && (int)$debtor['status'] === DEBTOR_STATUS_ACTIVE ? ' selected' : '' ?>><?php echo t('Active'); ?></option>
        <option value="<?php echo DEBTOR_STATUS_INACTIVE ?>"<?php echo is_array($debtor) && isset($debtor['status']) && (int)$debtor['status'] === DEBTOR_STATUS_INACTIVE ? ' selected' : '' ?>><?php echo t('Inactive'); ?></option>
        <option value="<?php echo DEBTOR_STATUS_ERROR ?>"<?php echo is_array($debtor) && isset($debtor['status']) && (int)$debtor['status'] === DEBTOR_STATUS_ERROR ? ' selected' : '' ?>><?php echo t('Error'); ?></option>
      </select>
      <br>
      <input type="submit" name="submit_edit_debtor" value="<?php echo t('Save'); ?>">
      <br>
    </fieldset>
  </form>
<?php
      break;
    case 'log':
?>
  <h2><?php echo t('E-mail log'); ?></h2>
  <p><?php echo t('This page shows when e-mails has been sent to recipients.'); ?></p>
  <table border="1">
    <tr>
      <th><?php echo t('Date'); ?></th>
      <th><?php echo t('Type'); ?></th>
      <th><?php echo t('Text'); ?></th>
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
          case VERBOSE_OFF: # no info at all
            echo t('Off');
            break;
          case VERBOSE_ERROR: # only errors
            echo t('Error');
            break;
          case VERBOSE_INFO: # above and things that changes
            echo t('Debtor');
            break;
          case VERBOSE_DEBUG: # above and verbose info
          case VERBOSE_DEBUG_DEEP:
            echo t('Debug');
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
<h1><?php echo t('E-mail'); ?></h1>
<p><?php echo t('This page shows how a mail would look like if it was sent on').' '.$dateto; ?>.</p>
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
  <h2><?php echo t('Reference rate'); ?></h2>
  <p><?php echo t('This page shows the national reference rate.'); ?></p>
  <table border="1">
    <tr>
      <th><?php echo t('Date'); ?></th>
      <th><?php echo t('Interest rate'); ?> (%)</th>
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
  <h2><?php echo t('National interest rate'); ?></h2>
  <form action="?" method="post">
    <fieldset>
      <label><?php echo t('Valid from'); ?> (YYYY-MM-DD):</label>
      <input type="text" name="updated">
      <br>

      <label><?php echo t('Reference rate'); ?>:</label>
      <input type="text" name="referencerate">
      <br>

      <input type="submit" name="submit_edit_debtor" value="<?php echo t('Save'); ?>">
      <br>
    </fieldset>
  </form>
*/
    break;

  }
?>
</body>
</html>
