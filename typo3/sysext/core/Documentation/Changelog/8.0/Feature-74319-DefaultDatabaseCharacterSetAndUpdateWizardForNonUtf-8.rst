
.. include:: ../../Includes.txt

================================================================================
Feature: #74319 - Default database character set and update wizard for non UTF-8
================================================================================

See :issue:`74319`

Description
===========

If you install TYPO3 to an existing database with a default charset other than utf-8,
TYPO3 will create tables with the default charset of that database.
The install tool should check the default charset and notify the user if it is not utf-8.

Furthermore the install tool should check for this issue too and provide an update
wizard to fix this (=set the default charset to utf-8 and NOT convert existing tables
to utf-8).
A default charset set other than utf-8 leads to non-utf-8 tables when updating the
database via the install tool or installing extensions.


Impact
======

During installation on database select the default charset of the database is checked.
If it is not utf-8 the installation will not proceed and the user is notified of the issue.

For existing installations the install tool also provides an environment check and an
upgrade wizard which changes the default database character set. The update wizard
will NOT convert any existing tables though!

.. index:: Backend, Database
