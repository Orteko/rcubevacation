<?php
/*
Configuration parameters for the different drivers go here

*/


// Defines which driver is active. Valid options are: ftp, virtual or setuid
$rcmail_config['driver'] = 'setuid';

/*
	Virtual vacation. Use this with virtual users
*/



// For security reasons it's not recommended that the Roundcube user (in db.inc.php)
// should be able to edit the Postfix user tables.
// Specify a DSN with sufficient privileges here or leave empty so the system DSN is used.
$rcmail_config['virtual']['dsn'] = 'mysql://roundcube:password@localhost';
// Postfix server only. See http://www.postfix.org/VIRTUAL_README.html#autoreplies
$rcmail_config['virtual']['transport'] = 'vacation.yourdomain.org';

// Database used by the mailserver. The vacation and virtual alias tables must exist in this database
$rcmail_config['virtual']['dbase'] = 'postfix';

// Always maintain an $email -> $email alias in the alias table
$rcmail_config['virtual']['always_keep_copy'] = true;

// Parameters: %e = email address, %d = domain, %i = domain_id, %g = goto . The latter is used for the transport
// %g expands to john@domain.org@vacation.yourdomain.org
// %m is required as the Roundcube database is different from the mailserver's database.

$rcmail_config['virtual']['select_query'] = "SELECT destination FROM %m.virtual_aliases WHERE source='%e' AND destination='%g'";

// Aliases are recreated when saving vacation settings
$rcmail_config['virtual']['delete_query'] = "DELETE FROM %m.virtual_aliases WHERE domain_id=%i AND source='%e'";

$rcmail_config['virtual']['insert_query'] = "INSERT INTO %m.virtual_aliases (domain_id,source,destination) VALUES (%i,'%e','%g')";
// If the alias table uses domain_id (integer) rather than domain (varchar), specify a query here to lookup domain_id
// The result will be assigned to %i
$rcmail_config['virtual']['domain_lookup_query'] = "SELECT id FROM postfix.virtual_domains WHERE name='%d'";
/*
	setuid backend parameters
*/

$rcmail_config['setuid']['setuid_executable'] = '/usr/bin/squirrelmail_vacation_proxy';
$rcmail_config['setuid']['vacation_executable'] = '/usr/bin/vacation';
$rcmail_config['setuid']['vacation_flags'] = ''; // See man vacation for valid flags
$rcmail_config['setuid']['vacation_message'] = '.vacation.msg';
$rcmail_config['setuid']['vacation_database'] = '.vacation.db';
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
