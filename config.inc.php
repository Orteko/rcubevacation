<?php
/*
	Configuration file for Roundcube vacation plugin.

	Default configuration is done in config.ini

	In config.inc.php shared configuration items are specified:

	1) Default body and subject
	2) .forward settings.
	 

*/

// Optionally specify a default subject
$rcmail_config['default']['subject'] = "Default subject";
// Optionally specify a file that contains a default message
$rcmail_config['default']['body'] = 'default.txt';

/*
	Configure these forward if you use either SSHFTP, FTP or Setuid as a driver.
	The defaults configured here should be ok for most cases.
*/

$rcmail_config['forward']['binary'] = '/usr/bin/vacation';
$rcmail_config['forward']['flags'] = ''; // Unsupported the moment

// File that contains the body & subject of the out of office mail. Required
$rcmail_config['forward']['message'] = ".vacation.msg";

// Database file for vacation binary. Default should be ok. Required
$rcmail_config['forward']['database'] = ".vacation.db";

// If set to true use -a $alias in the .forward file and allow the user to select the aliases.
// By default the identities are shown if available
$rcmail_config['forward']['alias_identities'] = true;

// If the vacation binary supports setting the envelop sender, set this to the right parameter.
// *BSD seems to use '-R' and Debian '-z'. See 'man vacation' for more info.
$rcmail_config['forward']['set_envelop_sender'] = false;

// If vacation/auto-reply gets disabled, should we keep the .vacation.msg? Optional.
$rcmail_config['forward']['always_keep_message'] = true;

?>
