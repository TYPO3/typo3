
.. include:: /Includes.rst.txt

================================================================
Breaking: #74124 - Removed sys_file_reference field downloadname
================================================================

See :issue:`74124`

Description
===========

The database table `sys_file_reference` comes with the database field `downloadname` but it is not used or displayed anywhere.

The database field has been removed.


Impact
======

Using this field in SQL statements will result in no output of files and
potentially SQL errors.


Affected Installations
======================

Any installation with an extension using this database field. Any installation with TypoScript using this field
directly in the SQL definition when fetching a sys_file_reference record.


Migration
=========

If this field was used before and is still needed, re-create this field in the `ext_tables.sql` of your extension.

.. index:: Database, FAL
