
<?php

/*
* ==============================================================================================================
*
* The minimum requirements to get phpList working are in this file.
* If you are interested in tweaking more options, check out the config_extended.php file
* or visit http://resources.phplist.com/system/config
*
* ** NOTE: To use options from config_extended.php, you need to copy them to this file **
*
==============================================================================================================
*/

// what is your Mysql database server hostname
$database_host = 'localhost';

// what is the name of the database we are using
$database_name = 'newsletter';

// what user has access to this database
$database_user = 'newsletter_db_user';

// and what is the password to login to control the database
$database_password = 'Loz1nka2026$';

// SMTP settings
define('PHPMAILERHOST', 'localhost');
define('PHPMAILERPORT', 25);
define('PHPMAILER_SECURE', false);
define('PHPMAILERAUTH', false);

// if TEST is set to 1 (not 0) it will not actually send ANY messages, but display what it would have sent
define('TEST', 0);

/*
==============================================================================================================
*
* Settings for handling bounces
*
==============================================================================================================
*/

// $message_envelope = 'listbounces@yourdomain';

$bounce_protocol = 'pop';

define('MANUALLY_PROCESS_BOUNCES', 1);

$bounce_mailbox_host = 'localhost';
$bounce_mailbox_user = 'popuser';
$bounce_mailbox_password = 'password';

$bounce_mailbox_port = '110/pop3/notls';
//$bounce_mailbox_port = "110/pop3";
//$bounce_mailbox_port = "995/pop3/ssl/novalidate-cert";

$bounce_mailbox = '/var/mail/listbounces';

$bounce_mailbox_purge = 1;

$bounce_mailbox_purge_unprocessed = 1;

$bounce_unsubscribe_threshold = 5;

define('HASH_ALGO', 'sha256');