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
  # 2018-08-07 19:15:00 - adding balance
  # 2018-08-08 20:31:00 - adding balance
  # 2018-08-08 17:04:00 - adding balance

  require_once('include/functions.php');

  start_translations();

  # parameters

  $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
  $address = isset($_REQUEST['address']) ? $_REQUEST['address'] : false;
  $amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : false;
  $city = isset($_REQUEST['city']) ? $_REQUEST['city'] : false;
  $collectioncost = isset($_REQUEST['collectioncost']) ? $_REQUEST['collectioncost'] : false;
  $cost = isset($_REQUEST['cost']) ? $_REQUEST['cost'] : false;
  $dateto = isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : false;
  $day_of_month = isset($_REQUEST['day_of_month']) ? $_REQUEST['day_of_month'] : false;
  $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : false;
  $email_bcc = isset($_REQUEST['email_bcc']) ? $_REQUEST['email_bcc'] : false;
  $happened = isset($_REQUEST['happened']) ? $_REQUEST['happened'] : false;
  $id_balance = isset($_REQUEST['id_balance']) ? $_REQUEST['id_balance'] : false;
  $id_debtors = isset($_REQUEST['id_debtors']) ? $_REQUEST['id_debtors'] : false;
  $id_properties = isset($_REQUEST['id_properties']) ? $_REQUEST['id_properties'] : false;
  $id_templates = isset($_REQUEST['id_templates']) ? $_REQUEST['id_templates'] : false;
  $invoicedate = isset($_REQUEST['invoicedate']) ? $_REQUEST['invoicedate'] : false;
  $invoicenumber = isset($_REQUEST['invoicenumber']) ? $_REQUEST['invoicenumber'] : false;
  $last_reminder = isset($_REQUEST['last_reminder']) ? $_REQUEST['last_reminder'] : false;
  $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : false;
  $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : false;
  $onlychanges = isset($_REQUEST['onlychanges']) ? $_REQUEST['onlychanges'] : false;
  $orgno = isset($_REQUEST['orgno']) ? $_REQUEST['orgno'] : false;
  $payment = isset($_REQUEST['payment']) ? $_REQUEST['payment'] : false;
  $percentage = isset($_REQUEST['percentage']) ? $_REQUEST['percentage'] : false;
  $property = isset($_REQUEST['property']) ? $_REQUEST['property'] : false;
  $referencerate = isset($_REQUEST['$referencerate']) ? $_REQUEST['$referencerate'] : false;
  $reminder_days = isset($_REQUEST['reminder_days']) ? $_REQUEST['reminder_days'] : false;
  $remindercost = isset($_REQUEST['remindercost']) ? $_REQUEST['remindercost'] : false;
  $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : false;
  $template = isset($_REQUEST['template']) ? $_REQUEST['template'] : false;
  $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : false;
  $value = isset($_REQUEST['value']) ? $_REQUEST['value'] : false;
  $view = isset($_REQUEST['view']) ? $_REQUEST['view'] : false;
  $zipcode = isset($_REQUEST['zipcode']) ? $_REQUEST['zipcode'] : false;

  switch ($action) {
    case 'insert_update_balance':
      $iu = array(
          'amount' => $amount,
          'cost' => $cost,
          'happened' => $happened,
          'message' => $message,
          'payment' => $payment,
          'type' => $type
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
        'email' => $email,
        'email_bcc' => $email_bcc,
        'invoicedate' => $invoicedate,
        'invoicenumber' => $invoicenumber,
        'last_reminder' => $last_reminder,
        'name' => $name,
        'orgno' => $orgno,
        'percentage' => (float)str_replace(',', '.', $percentage) / 100,
        'reminder_days' => $reminder_days,
        'remindercost' => $remindercost,
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
    case 'insert_update_property':

      $iu = array(
        'property' => $property,
        'value' => $value,
        'updated' => date('Y-m-d H:i:s'),
      );

      # no id - try to find it by name
      if (!$id_properties) {
        $sql = '
          SELECT
            id
          FROM
            properties
          WHERE
            property="'.dbres($link, $property).'"';
          $properties = db_query($link, $sql);
        if (count($properties)) {
          $id_properties = $properties[0]['id'];
        }
      }

      # new property
      if (!$id_properties) {

        $iu['created'] = date('Y-m-d H:i:s');
        $iu = dbpia($link, $iu);
        $sql = '
          INSERT INTO properties ('.
            implode(', ', array_keys($iu)).
          ') VALUES('.
            implode(', ', $iu).
          ')';
      # update property
      } else {
        $iu = dbpua($link, $iu);
        $sql = '
          UPDATE
            properties
          SET
            '.implode(', ', $iu).'
          WHERE
            id="'.dbres($link, $id_properties).'"
          ';
      }

      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      header('Location: ?view=properties');
      die();
    case 'delete_balance':

      # new property
      if (!$id_balance) {
        echo t('Missing').' id_properties';
        die();
      # update property
      }

      # new property
      if (!$id_debtors) {
        echo t('Missing').' id_debtors';
        die();
      # update property
      }

      $iu = dbpua($link, $iu);
      $sql = '
        DELETE FROM
          invoicereminder_balance
          '.implode(', ', $iu).'
        WHERE
          id="'.dbres($link, $id_balance).'"
        ';

      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      header('Location: ?view=balance&id_debtors='.$id_debtors);
      die();
    case 'delete_property':

      # new property
      if (!$id_properties) {

        echo t('Missing').' id_properties';
        die();
      # update property
      }

      $iu = dbpua($link, $iu);
      $sql = '
        DELETE FROM
          properties
          '.implode(', ', $iu).'
        WHERE
          id="'.dbres($link, $id_properties).'"
        ';

      $r = db_query($link, $sql);
      if ($r === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      header('Location: ?view=properties');
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

      foreach ($debtors as $k => $debtor) {
        $debtors[$k]['balance_history'] = balance_history($link, $debtor['id']);
      }

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
    case 'edit_property':

      # was there a debtor requested?
      if ($id_properties) {
        # find the debtor
        $sql = '
          SELECT
            *
          FROM
            properties
          WHERE
            id="'.dbres($link, $id_properties).'"
          ';
        $properties = db_query($link, $sql);
        if ($properties === false) {
          cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
          die(1);
        }

        # was there a debtor found?
        if (count($properties)) {
          # then take that
          $property = reset($properties);
        } else {
          $property = false;
        }

      } else {
        $property = false;
      }

      # find the debtor
      $sql = '
        SELECT
          *
        FROM
          properties
        ';
      $properties = db_query($link, $sql);
      if ($properties === false) {
        cl($link, VERBOSE_ERROR, db_error($link).' SQL: '.$sql);
        die(1);
      }

      # walk available properties
      foreach ($available_properties as $available_property) {
        $found = false;

        # not editing property or this is not the edited property
        if ($property === false || $property['property'] !== $available_property) {
          # walk properties in database
          foreach ($properties as $property_in_database) {
            # is the property in database the same as the available property
            if ($property_in_database['property'] === $available_property) {
              # mark as found
              $found = true;
              break;
            }
          }
        }

        if (!$found) {
          $tmp[] = $available_property;
        }
      }
      $available_filtered_properties = $tmp;

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

    case 'mail':
      $templatefile = false;

      $templates = scandir(TEMPLATE_DIR);

      $tmp = array();
      foreach ($templates as $k=> $file) {
        if (!is_file(TEMPLATE_DIR.$file)) {
          continue;
        }
        $tmp[] = array(
          'id' => $k,
          'name' => $file
        );

        if ($k === (int)$id_templates) {
          $templatefile = TEMPLATE_DIR.$file;
        }
      }
      $templates = $tmp;

      $mail = compose_mail($link, $id_debtors, $templatefile, true, $dateto);
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
    case 'properties':
      $sql = '
        SELECT
          *
        FROM
          properties
        ORDER BY
          property
        ';
      $properties = db_query($link, $sql);
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
  <li><a href="?view=properties"><?php echo t('Properties'); ?></a></li>
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
      <th><?php echo t('Amount'); ?></th>
      <th><?php echo t('Interest'); ?> (%)<br><?php echo t('Accrued'); ?></th>
      <th><?php echo t('Costs'); ?></th>
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
        $bend = end($debtor['balance_history']['history']);
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
        <?php echo money($bend['amount_accrued']); ?> kr
      </td>
      <td class="percentage">
        <?php echo isset($bend['rate_accrued']) ? $bend['rate_accrued'] * 100 : '?'; ?>%<br>
<?php
        echo isset($bend['interest_accrued']) ? money($bend['interest_accrued']) : '';
?> kr
      </td>
      <td class="amount">
<?php
        echo isset($bend['cost_accrued']) ? money($bend['cost_accrued']) : '';
?>
      </td>
      <td class="amount">
        <?php echo isset($bend['amount_accrued'], $bend['interest_accrued'], $bend['cost_accrued']) ? money($bend['amount_accrued'] + $bend['interest_accrued'] + $bend['cost_accrued']) : ''; ?> kr
        <br>
        (<?php echo $debtor['balance_history']['special']['date_last_year_end'] ?>: <?php echo money($debtor['balance_history']['special']['total_last_year_end']) ?> kr)
      </td>
      <td>
        <?php echo $debtor['email']; ?><br>
        (<?php echo $debtor['email_bcc']; ?>)
      </td>
      <td><?php echo $debtor['balance_history']['special']['duedate']; ?></td>
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
      <td><?php echo date('Y-m-d', strtotime($row['happened'])); ?></td>
      <td class="amount">
        <?php echo money($row['amount']); ?>
      </td>
      <td class="amount">
        <?php echo money($row['cost']); ?>
      </td>
      <td class="amount">
        <?php echo money($row['payment']); ?>
      </td>
      <td><?php echo $row['message']; ?></td>
      <td>
        <a href="?view=edit_balance&amp;id_balance=<?php echo $row['id']?>&amp;id_debtors=<?php echo $id_debtors; ?>"><?php echo t('Edit'); ?></a>
        <a href="?action=delete_balance&amp;id_balance=<?php echo $row['id']?>&amp;id_debtors=<?php echo $id_debtors; ?>"><?php echo t('Delete') ?></a>
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

      <label><?php echo t('Debtor'); ?>:</label><br>
      <span class="value"><?php echo is_array($debtor) && isset($debtor['name']) ? $debtor['name'] : 'Ingen' ?> (#<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>)</span>
      <input type="hidden" name="id_debtors" value="<?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : '' ?>">
      <br>

      <label><?php echo t('Row type'); ?>:</label><br>
      <select name="type">
        <option value="<?php echo BALANCE_TYPE_NORMAL; ?>"<?php echo is_array($balance) && isset($balance['type']) && (int)$balance['type'] === BALANCE_TYPE_NORMAL ? ' selected' : '' ?>><?php echo t('Normal'); ?></option>
        <option value="<?php echo BALANCE_TYPE_DUEDATE; ?>"<?php echo is_array($balance) && isset($balance['type']) && (int)$balance['type'] === BALANCE_TYPE_DUEDATE ? ' selected' : '' ?>><?php echo t('Due date')?></option>
      </select>
      <br>

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

      <label><?php echo t('Date'); ?>:</label><br>
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
      <span class="value"><?php echo is_array($debtor) && isset($debtor['id']) ? $debtor['id'] : t('New debtor') ?></span>
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
      <input type="number" min=0 step="0.01" name="percentage" value="<?php echo is_array($debtor) && isset($debtor['percentage']) ? $debtor['percentage'] * 100 : '' ?>">
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
    case 'edit_property':
?>
  <h2><?php echo t('Edit property'); ?></h2>
  <form action="?" method="post">
    <fieldset>

      <input type="hidden" name="action" value="insert_update_property">

      <label>#:</label><br>
      <span class="value"><?php echo is_array($property) && isset($property['id']) ? $property['id'] : t('New property') ?></span>
      <input type="hidden" name="id_properties" value="<?php echo is_array($property) && isset($property['id']) ? $property['id'] : '' ?>">
      <br>

      <label><?php echo t('Property'); ?>:</label><br>
      <select name="property">
        <?php foreach ($available_filtered_properties as $key => $propertyname) { ?>
        <option value="<?php echo $propertyname ?>"<?php echo is_array($property) && isset($property['property']) && $property['property'] === $propertyname ? ' selected' : '' ?>><?php echo $propertyname; ?></option>
        <?php } ?>
      </select>
      <br>

      <label><?php echo t('Value'); ?>:</label><br>
      <input type="text" name="value" value="<?php echo is_array($property) && isset($property['value']) ? $property['value'] : '' ?>">
      <br>

      <input type="submit" name="submit_edit_property" value="<?php echo t('Save'); ?>">
      <br>
    </fieldset>
  </form>
<?php
      break;
    case 'history':
?>
  <h2><?php echo t('Debt history'); ?></h2>
  <p><?php echo t('Debtor'); ?>: <?php echo $debtor['name']?> (#<?php echo $debtor['id']; ?>).
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
        if ($row['payment_paid_this_day'] !== 0) {
          $changes[] = (-$row['payment_paid_this_day'] > 0 ? '+' : '').money(-$row['payment_paid_this_day']);
        }
        # any change this day?
        if (count($changes)) {
?>
          (<?php echo implode(', ', $changes) ?>)
<?php
        }
?>
      </td>
      <td class="percentage"><?php echo percentage(($row['rate_accrued'])*100).' ('.percentage($row['refrate']*100).')' ?></td>
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
?>
<h1><?php echo t('E-mail'); ?></h1>
<p><?php echo t('This page shows how a mail would look like if it was sent on').' '.$dateto; ?>.</p>

<form action="?" method="get">
  <fieldset>
    <input type="hidden" name="id_debtors" value="<?php echo $id_debtors; ?>">
    <input type="hidden" name="view" value="mail">
    <input type="hidden" name="dateto" value="<?php echo $dateto?>">

    <label><?php echo t('Template'); ?>:</label>
    <select name="id_templates">
<?php
      foreach ($templates as $template) {
?>
      <option value="<?php echo $template['id']; ?>"<?php echo $template['id'] === (int)$id_templates ? ' selected' : ''; ?>><?php echo $template['name']; ?><?php echo $template['name'] === $mail['templatefile_default'] ? ' ('.t('Active').')' : ''; ?></option>
<?php
      }
?>
    </select>
    <input type="submit" value="<?php echo t('Show'); ?>">
    <br>
  </fieldset>
</form>

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

    case 'properties':
?>
  <h2><?php echo t('Properties'); ?></h2>
  <p><?php echo t('This page shows properties that affect templates.') ?>
  <p>
    <a href="?view=edit_property"><?php echo t('New property'); ?></a>
  </p>
  <table border="1">
    <tr>
      <th><?php echo t('Property'); ?></th>
      <th><?php echo t('Value'); ?></th>
      <th><?php echo t('Manage'); ?></th>
    </tr>
<?php
      # walk debtors
      foreach ($properties as $k => $row) {
?>
    <tr>
      <td>
        <?php echo $row['property'] ?>
      </td>
      <td>
        <?php echo $row['value'] ?>
      </td>
      <td>
        <a href="?view=edit_property&amp;id_properties=<?php echo $row['id']?>"><?php echo t('Edit') ?></a>
        <a href="?action=delete_property&amp;id_properties=<?php echo $row['id']?>"><?php echo t('Delete') ?></a>
      </td>
    </tr>
<?php
      }
?>
  </table>
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
    break;

  }
?>
</body>
</html>
