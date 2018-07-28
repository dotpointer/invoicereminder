# Invoicereminder

Reminds of invoice and loan payments.

## Purpose

To remind late payers that they have invoices and loans to pay
and gives them instructions to do so.

## What it does

Summarizes what to pay, composes and sends mail to payers.

## How it works

The service is executed by cron job and composes mails using templates.

# Requirements / tested on

-  MariaDB (or MySQL)
-  PHP 5.6 / 7.0 with cURL, MySQL/MariaDB
-  Mail server to mail from.

## Getting Started

These instructions will get you a copy of the project up and running on your
local machine for development and testing purposes. See deployment for notes on
how to deploy the project on a live system.

### Prerequisites

What things you need to install the software and how to install them

```
- Debian Linux 9 or similar system
- nginx
- MariaDB (or MySQL)
- PHP
- PHP-FPM
- PHP-MySQLi
```

Setup the nginx web server with PHP-FPM support and MariaDB/MySQL.

In short: apt-get install nginx mariadb-server php-fpm php-mysqli php-mbstring
and then configure nginx, PHP and setup a user in MariaDB.

### Installing

Head to the nginx document root and clone the repository:

```
cd /var/www/html
git clone https://gitlab.com/dotpointer/invoicereminder.git
cd invoicereminder/
```

Import database structure, located in sql/database.sql

Standing in the project root directory login to the database:

```
mariadb/mysql -u <username> -p

```

If you do not have a user for the web server, then login as root and do
this to create the user named www with password www:

```
CREATE USER 'www'@'localhost' IDENTIFIED BY 'www';
```

Then import the database structure and assign a user to it, replace
www with the web server user in the database system:
```
SOURCE sql/database.sql
GRANT ALL PRIVILEGES ON invoicereminder.* TO 'www'@'localhost';
FLUSH PRIVILEGES;
```

Fill in the configuration in include/setup.php.

You also need to add two cron jobs to update the national reference rate and to send mails.

Add these to /etc/crontab with a new line on the end:

0 1 3 1,7 * root /usr/bin/php  /var/www/html/invoicereminder/worker.php --action=updatereference

0 18 26 * * root /usr/bin/php /var/www/html/invoicereminder/worker.php --action=remind

## Authors

* **Robert Klebe** - *Development* - [dotpointer](https://gitlab.com/dotpointer)

See also the list of
[contributors](https://gitlab.com/dotpointer/invoicereminder/contributors)
who participated in this project.

## License

This project is licensed under the MIT License - see the
[LICENSE.md](LICENSE.md) file for details.

Contains dependency files that may be licensed under their own respective
licenses.
