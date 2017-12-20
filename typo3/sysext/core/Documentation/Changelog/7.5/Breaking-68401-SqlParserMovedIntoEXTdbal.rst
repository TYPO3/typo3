
.. include:: ../../Includes.txt

================================================
Breaking: #68401 - SqlParser moved into EXT:dbal
================================================

See :issue:`68401`

Description
===========

The SQL Parser included with the core has not been in use by anything
except EXT:dbal for some time. The SQL parser has been merged with the
version in EXT:dbal which now provides parsing and compiling of SQL
statements for MySQL as well as other DBMS.


Impact
======

There is no impact for the core as EXT:dbal was the sole user of the SQL
parser and it has been migrated into EXT:dbal.

As the parsing and the compiling of SQL statements has been separated into
multiple classes the non-public interface of `SqlParser` has changed.
Classes extending SqlParser need to be adjusted to the new interface.


Affected Installations
======================

Installations with 3rd party extensions that use `\TYPO3\CMS\Core\Database\SqlParser`.


Migration
=========

Update the code to use `\TYPO3\CMS\Dbal\Database\SqlParser` instead of
`\TYPO3\CMS\Core\Database\SqlParser` or install EXT:compatibility6 which
maps the old class names to the new ones in EXT:dbal.


.. index:: PHP-API, Database, ext:dbal
