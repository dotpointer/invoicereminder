<?php
header('Content-Type: text/javascript');

require_once('functions.php');

# changelog
# 2018-11-16 17:06:00 - source from autoqueuer, adding default creditor/debtor filter

?>
/*jslint white: true, this: true, browser: true, long: true */
/*global window,$,jQuery,view*/
let	ir = {
  view: ""
};

(function() {
  "use strict";

  // run when document is ready
  $(window.document).ready(() => {

    ir.view = view;

      // find out what view that was requested
    switch (view) {
<?php if (is_logged_in()) { ?>
      case '':
        $('#select_creditor,#select_debtor').on('change', () => {
          const params = [];

          if ($('#select_creditor').val().length) {
            params.push('id_contacts_creditor=' + $('#select_creditor').val());
          }
          if ($('#select_debtor').val().length) {
            params.push('id_contacts_debtor=' + $('#select_debtor').val());
          }
          window.location.href = './?' + params.join('&');
        });
        break;
<?php } ?>
    }
  });
}());
