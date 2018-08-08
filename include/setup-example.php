<?php

  # changelog
  # 2018-07-28 16:23:10 - original
  # 2018-07-28 16:24:00 - example file
  # 2018-07-28 17:01:00 - renaming from invoicenagger to invoicereminder
  # 2018-08-08 17:44:00 - adding configuration with log settings

  # database setup
  define('DATABASE_HOST', 'localhost');
  define('DATABASE_USERNAME', 'www');
  define('DATABASE_PASSWORD', 'www');
  define('DATABASE_NAME', 'invoicereminder');

  define('REPLY_TO', 'Your name <your@email.com>');
  define('FROM', 'Your name <your@email.com>');

  # configuration
  $config = array(
    'main' => array(
      'verbose' => VERBOSE_OFF,
      'loglevel' => VERBOSE_ERROR
    )
  );
?>
