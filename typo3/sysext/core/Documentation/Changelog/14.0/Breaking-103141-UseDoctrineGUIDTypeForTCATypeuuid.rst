..  include:: /Includes.rst.txt

..  _breaking-103141-1733501374:

============================================================
Breaking: #103141 - Use doctrine GUID type for TCA type=uuid
============================================================

See :issue:`103141`

Description
===========

The TYPO3 Doctrine implementation can now handle the proper
matching database type for UUID's.

Postgresql natively supports the UUID data type and is way faster
than the prior `VARCHAR(36)` generated from the string type.

The Doctrine DBAL GUID type uses `CHAR(26)` as the fixed field column size
for non-postgres databases, which is compatible as long
as valid UUID values were persisted in the configured database
table.

Impact
======

TYPO3 database tables can now natively properly apply the suitable
GUID column type when configured as TCA `type=uuid`.

A prerequisite for this is that you only have valid UUID's stored
in the database table, otherwise the database update will report
an error when applying migrations.


Affected installations
======================

Projects with database table columns set as TCA `type=uuid`.

Error are likely to occur, if invalid UUID data is stored
in field columns configured with this type.


Migration
=========

Use the database analyzer to migrate the database fields.
Invalid values are not migrated and need to be manually
cleaned up in affected instances.

..  index:: Database, FullyScanned, ext:core
