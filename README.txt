
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

Licensing:
----------
This product is distributed under the GPL. Please read through the file
LICENSE in Roundcube's root directory for more information about our license.

Credits
-------
Peter Ruiter for his initial work on the plugin.


Requirements
------------
In order for the SQL backend to work properly, the Postfix SMTP-server has to be configured to work with an autoreply transport.
More information can be found on http://www.postfix.org/VIRTUAL_README.html#autoreplies
The database should match the one on http://workaround.org/ispmail/lenny/preparing-the-database

Security
--------
From a security point of view it's recommended to use a dedicated user for the virtual vacation setup. This user must have DELETE and INSERT privileges to
database 'postfix', table 'vacation' and database 'postfix', table 'virtual_aliases'.

CREATE USER 'virtual_vacation'@'localhost' IDENTIFIED BY 'choose_a_password';
GRANT DELETE,INSERT ON `postfix` . vacation TO 'virtual_vacation'@'localhost';
GRANT DELETE,INSERT,UPDATE ON `postfix` . vacation_notification TO 'virtual_vacation'@'localhost';
GRANT DELETE,INSERT ON `postfix` . virtual_aliases TO 'virtual_vacation'@'localhost';

Drivers
-------
The following drivers are available to do the low level work:
- FTP. This driver uploads the .forward file to the user's homedirectory.
- Setuid. This driver places the .forward file in the user's homedirectory using the squirrel setuid binary.
- SQL. This driver creates entries in the vacation table in a MySQL database and modifies the alias table.
At the moment the SQL driver is tailored towards Postfix but can be modified to suit other configurations

Known bugs / limitations
------------------------
- SQL driver: Updating an existing vacation message in SQL driver does not work.
- GUI: Saving settings does not redirect to vacation plugin page
- Dutch translation improper

Todo
----
- support updating of existing vacation message
- more testing of all driver
- testing vacation.pl . It's a dependency hell now
- flexible database scheme support
- Postgresql support
- LDAP support
- rewriting the parse method in DotForward

Contact
-------
Project URL: https://sourceforge.net/projects/rcubevacation/
By e-mail: jaspersl @ gmail . com
