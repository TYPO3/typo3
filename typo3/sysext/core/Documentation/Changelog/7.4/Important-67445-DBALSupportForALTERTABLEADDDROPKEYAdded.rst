
.. include:: ../../Includes.txt

===================================================================
Important: #67445 - DBAL support for ALTER TABLE ADD/DROP KEY added
===================================================================

See :issue:`67445`

Description
===========

The prefix used to build the name of indexes in a database schema has
been changed. The prefix is used to ensure that an index name is unique
within a database schema.

Formerly the requested index name was prepended with the table name to
which the index was added. In some cases this results in index names that
exceeded the valid identifier length on all DBMS except MS SQL Server.
The silent truncation of these identifiers results in non-unique names or
index names that can not be matched to the original name.

With TYPO3 7.4 the prefix used for index names has been changed to
a unique constant length prefix. Due to this all non-primary indexes need
to be dropped and re-created with a new name. The changes to the database
will be performed by the Upgrade Wizard in the Install Tool.


.. index:: Database, ext:dbal
