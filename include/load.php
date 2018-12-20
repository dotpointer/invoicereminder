<?php
header('Content-Type: text/javascript');

require_once('functions.php');

# changelog
# 2018-11-16 17:06:00 - source from autoqueuer, adding default creditor/debtor filter
# 2018-11-19 19:26:00 - adding charts
# 2018-11-19 19:47:00 - adjusting chart tool tips
# 2018-12-20 18:49:00 - moving translation to Base translate

start_translations(dirname(__FILE__).'/locales/');
?>
/*jslint white: true, this: true, browser: true, long: true */
/*global clientpumptypes,window,$,jQuery,toggler,Highcharts,files_queued_stats,
view,types,methods*/
let ir = {
  msg: <?php echo json_encode(get_translation_texts()); ?>,
  view: ""
};


(function() {
  "use strict";

  // run when document is ready
  $(window.document).ready(() => {

    ir.view = view;
    // to translate texts
    ir.t = function (s) {
      let found = false;
      // are the translation texts available?
      if (typeof ir.msg !== "object") {
        return s;
      }

      // walk the translation texts
      Object.keys(ir.msg).forEach(function (i) {
        if (
          found === false &&
          ir.msg[0] !== undefined &&
          ir.msg[1] !== undefined &&
          ir.msg[i][0] === s
        ) {
          found = ir.msg[i][1];
        }
      });

      if (found !== false) {
        return found;
      }

      return s;
    };
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
      case 'history':

        Highcharts.chart('charttotal', {
          xAxis: {
            categories: slimmed_history.map(day => day.d)
          },
          yAxis: {
            title: {
              text: ir.t('Amount')
            }
          },
          plotOptions: {
            series: {
                allowPointSelect: true
            }
          },
          series: [{
              data: slimmed_history.map(day => day.t),
              name: ir.t('Date')
          }],
          title: {
            text: ir.t('Total amount')
          },
          tooltip: {
            formatter: function() {
              return this.x + ': <b>' + Highcharts.numberFormat(this.y, 2, ',') + ' kr</b>';
            }
          }
        });

        Highcharts.chart('chartaccrued', {
          xAxis: {
            categories: slimmed_history.map(day => day.d)
          },
          yAxis: {
            title: {
              text: ir.t('Amount')
            }
          },
          plotOptions: {
            series: {
                allowPointSelect: true
            }
          },
          series: [{
              data: slimmed_history.map(day => day.a),
              name: ir.t('Date')
          }],
          title: {
            text: ir.t('Accrued interest')
          },
          tooltip: {
            formatter: function() {
              return this.x + ': <b>' + Highcharts.numberFormat(this.y, 2, ',') + ' kr</b>';
            }
          }
        });

        Highcharts.chart('chartprincipal', {
          xAxis: {
            categories: slimmed_history.map(day => day.d)
          },
          yAxis: {
            title: {
              text: ir.t('Amount')
            }
          },
          plotOptions: {
            series: {
                allowPointSelect: true
            }
          },
          series: [{
              data: slimmed_history.map(day => day.p),
              name: ir.t('Date')
          }],
          title: {
            text: ir.t('Principal amount')
          },
          tooltip: {
            formatter: function() {
              return this.x + ': <b>' + Highcharts.numberFormat(this.y, 2, ',') + ' kr</b>';
            }
          }
        });

        Highcharts.chart('chartinterestperday', {
          xAxis: {
              categories: slimmed_history.map(day => day.d)
          },
          yAxis: {
            title: {
              text: ir.t('Amount')
            }
          },
          plotOptions: {
              series: {
                  allowPointSelect: true
              }
          },
          series: [{
              data: slimmed_history.map(day => day.i),
              name: ir.t('Date')
          }],
          title: {
            text: ir.t('Interest per day')
          },
        tooltip: {
          formatter: function() {
            return this.x + ': <b>' + Highcharts.numberFormat(this.y, 2, ',') + ' kr</b>';
          }
        }
      });
      break;
<?php } ?>
    }
  });
}());
