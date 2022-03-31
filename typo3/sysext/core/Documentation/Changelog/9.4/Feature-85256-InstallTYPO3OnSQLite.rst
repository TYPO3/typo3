.. include:: /Includes.rst.txt

=========================================
Feature: #85256 - Install TYPO3 on SQLite
=========================================

See :issue:`85256`

Description
===========

The TYPO3 web installer allows to install the system on `SQLite` DBMS.

This platform can be selected if :php:`pdo_sqlite` is available in PHP, which is
often the case. SQLite can be a nice DBMS for relatively small instances and has
the advantage that no further server side daemon is needed.

Administrators must keep an eye on security if using this platform:

In SQLite, a database is stored in a single file. In TYPO3, its default location
is the var/sqlite path of the instance which is derived from environment variable
:php:`TYPO3_PATH_APP`. If that variable is **not** set which is
often the case in non-composer instances, **the database file will end up in the
web server accessible document root directory :file:`typo3conf/`**!

To prevent guessing the database name and simply downloading it, the installer appends
a random string to the database filename during installation. Additionally, the demo
Apache :file:`_.htaccess` file prevents downloading :file:`.sqlite` files. The demo
MicroSoft IIS web server configuration in file :file:`_web.config` comes with the same
restriction.

Administrators installing TYPO3 using the SQLite platform should thus test if the
database is downloadable from the web and take measures to prevent this by either
configuring the web server to deny this file, or - better - by moving the config folder
out of the web root, which is good practice anyway.


Impact
======

TYPO3 can be installed to run on SQLite. If choosing this option, administrators
must check the file is never delivered by the web server.


.. index:: Database
