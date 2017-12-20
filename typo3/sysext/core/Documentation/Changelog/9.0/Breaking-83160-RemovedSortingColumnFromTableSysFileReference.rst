.. include:: ../../Includes.txt

===========================================================================
Breaking: #83160 - Removed 'sorting' column from table 'sys_file_reference'
===========================================================================

See :issue:`83160`

Description
===========

The column :php:`sorting` has been removed from table :php:`sys_file_reference` as it was not used in TYPO3
core and lead to severe performance issues on instances with many records in the table.

Impact
======

Custom queries (e.g. from extensions) on table :php:`sys_file_reference` containing the column :php:`sorting` will lead to an SQL error.


Affected Installations
======================

All instances which use custom queries containing the mentioned column.

.. index:: Database, FAL, NotScanned
