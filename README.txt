
About
-----
The vacation plugin allows mail to be forwarded and/or auto-replied with a custom subject / message body.
Note that the actual auto-reply is done by a program such as /usr/bin/vacation or the virtual user aware vacation.pl


Features
--------
The following combination features are supported:
- keep a local copy of the mail
- forward the mail to another e-mail address
- send an out of office reply with custom subject & message body


Installation
------------
1) Unzip vacation.tar.gz in plugins/ 
2) Enable the vacation plugin in config/main.inc.php: $rcmail_config['plugins'] = array('vacation');
3) Edit driver plugins/vacation/config.inc.php
4) Set $rcmail_config['driver'] and configure related settings

Licensing
----------
This product is distributed under the GPL. Please read through the file
LICENSE in Roundcube's root directory for more information about the license.


Requirements
------------
In order for the SQL backend to work properly, the Postfix SMTP-server has to be configured to work with an autoreply transport.
More information on this subject can be found on http://www.postfix.org/VIRTUAL_README.html#autoreplies
The database schema should match the one on http://workaround.org/ispmail/lenny/preparing-the-database
A working vacation.pl , please find instructions in the OPTIONAL_PROGRAMS/virtual_vacation directory
It probably requires Perl libs which can be installed using CPAN.


Security
--------
From a security point of view it's recommended to use a dedicated user for the SQL driver for virtual users. 
This user must have DELETE and INSERT privileges to database 'postfix', table 'vacation' and database 'postfix',
table 'virtual_aliases'. It should not be able to access Roundcube's tables.

CREATE USER 'virtual_vacation'@'localhost' IDENTIFIED BY 'choose_a_password';
GRANT DELETE,INSERT ON `postfix` . vacation TO 'virtual_vacation'@'localhost';
GRANT DELETE,INSERT,UPDATE ON `postfix` . vacation_notification TO 'virtual_vacation'@'localhost';
GRANT DELETE,INSERT ON `postfix` . virtual_aliases TO 'virtual_vacation'@'localhost';

If Roundcube's main DSN is somehow affected by an SQL injection bug, no damage can be done to the postfix.* tables
Using a dedicated DSN is optional, the plugin works fine with the main DSN. 

Available drivers
------------------
The following drivers are available to do the low level work:
- FTP. This driver uploads the .forward file to the user's homedirectory.
- Setuid. This driver places the .forward file in the user's homedirectory using the squirrel setuid binary.
- SQL. This driver creates entries in the vacation table in a MySQL database and modifies the alias table.
At the moment the SQL driver is tailored towards Postfix/MySQL but can be modified to suit other configurations.


Writing a new driver
--------------------
1) Create relevant entries in config.inc.php. The name of array key must match the class name.
3) Create lib/$driver.class.php
3) Have your new driver extend VacationDriver
4) Implement abstract public methods from base class: init(), enable(),disable(),_get()
   You can access configuration settings using $this->cfg
   Form variables can be accessed directly using class properties, see save() method5
5) Write new private helper methods like is_active() if needed.
6) Test it
7) Submit a patch


Known bugs / limitations
------------------------
- GUI: Saving settings does not redirect to vacation plugin page
- Dutch translation is entirely accurate
- setuid driver is largely untested


Todo
----
- testing setuid driver. Requires a virtual machine with Linux.
- testing vacation.pl . It's a dependency hell now with Snow Leopard
- flexible database scheme support
- Postgresql support
- LDAP support


Credits
-------
The Postfixadmin team for creating the virtual vacation program.
Peter Ruiter for his initial work on the plugin.


Patches,feedback and suggestions
--------------------------------
Please submit patches and suggestions by e-mail (jaspersl @ gmail . com).
Project URL: https://sourceforge.net/projects/rcubevacation/
Suggestions are always welcome.