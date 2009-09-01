<?
/*
Configuration parameters for the different backends go here

*/
$config = $config['ftp'] = $config['virtual'] = $config['setuid'] = array();

// Defines which backend is active
$config['backend'] = 'virtual';

/*
	Virtual vacation. Use this with virtual users
*/

$config['virtual']['dsn'] = 'sqlite://./roundcube.db';
$config['virtual']['database'] = 'vacation';
// Postfix server only. See http://www.postfix.org/VIRTUAL_README.html#autoreplies
$config['virtual']['transport'] = '@vacation.yourdomain.org';

// Parameters: %e = email address, %d = domain, %g = goto . The latter is used for the transport
$config['virtual']['delete_query'] = "DELETE FROM alias WHERE domain='%d' AND goto='%g' AND email='%e' LIMIT 1";
$config['virtual']['insert_query'] = "INSERT INTO alias (email,domain,goto) VALUES ('%e','%d','%g')";

/*
	setuid backend parameters
*/

$config['setuid']['setuid_executable'] = '/usr/bin/squirrel_stuff';
$config['setuid']['vacation_flags'] = '-t 1'; // See man vacation for valid flags

/*
	FTP backend parameters
*/
$config['ftp']['server'] = 'localhost';
$config['ftp']['passive'] = true;
$config['ftp']['vacation_executable'] = '/usr/bin/vacation';
$config['ftp']['vacation_flags'] = '-t 1'; // See man vacation for valid flags
$config['ftp']['vacation_message'] = '.vacation.msg';
$config['ftp']['vacation_database'] = '.vacation.db';
?>