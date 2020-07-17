
.. include:: ../../Includes.txt

===========================================================
Breaking: #69028 - TCA type select - Drop neg_foreign_table
===========================================================

See :issue:`69028`


Description
===========

The following `TCA` keys for `type` `select` have been dropped and are no longer handled by the core:

* neg_foreign_table
* neg_foreign_table_where
* neg_foreign_table_prefix
* neg_foreign_table_loadIcons
* neg_foreign_table_imposeValueField

These setting were used in `select` for comma separated value relations in addition to `foreign_table`
to allow a second connected table. Relations for `neg_foreign_table` were stored as negative uids in the
field to distinguish them from relations to the table defined in `foreign_table`.

The functionality has been dropped without substitution and is no longer handled by the TYPO3 core.


Impact
======

Existing relations to the table defined in `neg_foreign_table` will be discarded when a record
with such a `TCA` configuration is saved to the database. The display of existing connected
records may be misleading.


Affected Installations
======================

This old school feature was never documented well and used by a very small amount of extensions.
Searching an instance for the keyword `neg_foreign_table` will reveal usages.


Migration
=========

In case records from multiple different tables must still be supported, the `TCA` configuration
should be adapted to use a `MM` intermediate table. For existing migrations a database migration
is required.


.. index:: TCA, Backend
