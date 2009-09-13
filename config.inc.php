<?php
/*
Configuration parameters for the different drivers go here

*/


// Defines which driver is active. Valid options are: ftp, virtual or setuid
$rcmail_config['driver'] = 'virtual';

/*
	Virtual vacation. Use this with virtual users
*/

// For security reasons it's not recommended that the Roundcube user (in db.inc.php)
// should be able to edit the Postfix user tables.
// Specify a DSN with sufficient privileges here or leave empty so the system DSN is used.
$rcmail_config['virtual']['dsn'] = '';
// Postfix server only. See http://www.postfix.org/VIRTUAL_README.html#autoreplies
$rcmail_config['virtual']['transport'] = 'vacation.yourdomain.org';

// Database used by Postfix
$rcmail_config['virtual']['dbase'] = 'postfix';
// Parameters: %e = email address, %d = domain,%i = domain_id, %g = goto . The latter is used for the transport
// %g expands to john@domain.org@vacation.yourdomain.org
$rcmail_config['virtual']['select_query'] = "SELECT 1 FROM postfix.virtual_aliases where source='%e' AND destination='%g'";
$rcmail_config['virtual']['delete_query'] = "DELETE FROM postfix.virtual_aliases WHERE domain_id='%d' AND destination='%g' AND source='%e' LIMIT 1";
$rcmail_config['virtual']['insert_query'] = "INSERT INTO postfix.virtual_aliases (domain_id,source,destination) VALUES (%i,'%e','%g')";
// If the alias table uses domain_id (integer) rather than domain (varchar), specify a query here.
// The result will be assigned to %i
$rcmail_config['virtual']['domain_lookup_query'] = "SELECT id FROM postfix.virtual_domains WHERE name='%d'";
/*
	setuid backend parameters
*/

$rcmail_config['setuid']['setuid_executable'] = '/usr/bin/squirrel_stuff';
$rcmail_config['setuid']['vacation_flags'] = ''; // See man vacation for valid flags

/*
	FTP backend parameters
*/
$rcmail_config['ftp']['server'] = 'ftp.xs4all.nl';
$rcmail_config['ftp']['passive'] = true;
$rcmail_config['ftp']['vacation_executable'] = '/usr/bin/vacation';
$rcmail_config['ftp']['vacation_flags'] = ''; // See man vacation for valid flags
$rcmail_config['ftp']['vacation_message'] = '.vacation.msg';
$rcmail_config['ftp']['vacation_database'] = '.vacation.db';
?>
