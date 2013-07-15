.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _introduction:

Introduction
------------


.. _what-does-it-do:

What does it do?
^^^^^^^^^^^^^^^^

It provides a layer for support of other core databases (DBMS) in
TYPO3 than the default database support based on MySQL. Further, each
layer can be selected on a per-table basis, thus offering storage of
content from TYPO3 in multiple databases and multiple formats.

The support is possible through the well known PHP database API ADOdb.
The extension also supports any user-defined layer you can make
yourself thus offering unlimited possibilities for connectivity.


.. _technical-details:

Technical details
^^^^^^^^^^^^^^^^^

This extension works by overriding class :code:`\TYPO3\CMS\Core\Database\DatabaseConnection`
in order to parse query and rebuild them in a way compatible with
multiple RDBMS.

Without the DBAL extension installed TYPO3 CMS works as usual
and with virtually no overhead in database
connectivity. DBAL offers support for other databases than
MySQL at the cost of parsing and rewriting queries.


.. _working-databases:

Working databases
^^^^^^^^^^^^^^^^^

The following databases have been tested and are known to work in
general. This means, not every aspect has been thoroughly checked, but
TYPO3 has been installed from scratch successfully, BE logins are
possible, and basic websites can be created (template, pages, content
elements):

- MySQL 5.x

- Microsoft SQL Server 2000

- PostgreSQL 7.x and 8.x

- Oracle 8, 9, 10 and 11

- Firebird 1.5.2

