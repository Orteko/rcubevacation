<?php
/*
Configuration parameters for the different drivers go here

*/


// Defines which driver is active
$rcmail_config['driver'] = 'virtual';

/*
	Virtual vacation. Use this with virtual users
*/

// For security reasons it's not recommended that the Roundcube user (in db.inc.php)
// should be able to edit the Postfix user tables.
// Specify a DSN with sufficient privileges here or leave empty so the system DSN is used.
$rcmail_config['virtual']['dsn'] = '';
$rcmail_config['virtual']['database'] = 'vacation';
// Postfix server only. See http://www.postfix.org/VIRTUAL_README.html#autoreplies
$rcmail_config['virtual']['transport'] = 'vacation.yourdomain.org';

$rcmail_config['virtual']['database'] = 'postfix';
// Parameters: %e = email address, %d = domain, %g = goto . The latter is used for the transport
// %g expands to john@domain.org@vacation.yourdomain.org
$rcmail_config['virtual']['select_query'] = "SELECT 1 FROM postfix.virtual_aliases where source='%e' AND destination='%g'";
$rcmail_config['virtual']['delete_query'] = "DELETE FROM postfix.virtual_aliases WHERE domain='%d' AND goto='%g' AND email='%e' LIMIT 1";
$rcmail_config['virtual']['insert_query'] = "INSERT INTO postfix.virtual_aliases (domain_id,source,destination) VALUES (1,'%e','%g')";

/*
	setuid backend parameters
*/

$rcmail_config['setuid']['setuid_executable'] = '/usr/bin/squirrel_stuff';
$rcmail_config['setuid']['vacation_flags'] = ''; // See man vacation for valid flags

/*
	FTP backend parameters
*/
$rcmail_config['ftp']['server'] = 'localhost';
$rcmail_config['ftp']['passive'] = true;
$rcmail_config['ftp']['vacation_executable'] = '/usr/bin/vacation';
$rcmail_config['ftp']['vacation_flags'] = ''; // See man vacation for valid flags
$rcmail_config['ftp']['vacation_message'] = '.vacation.msg';
$rcmail_config['ftp']['vacation_database'] = '.vacation.db';
?>
