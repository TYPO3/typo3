
.. include:: ../../Includes.txt

========================================================
Deprecation: #67297 - MySQL / DBMS field type conversion
========================================================

See :issue:`67297`

Description
===========

The Dbal\DatabaseConnection class provides generic functions that translate between native MySQL field types
and ADOdb meta field types. The generic functions `MySQLActualType()` and `MySQLMetaType` have been marked as
deprecated and should not be used any longer.


Impact
======

Although these are public functions the use was probably limited to the DBAL Extension.
If used however, they will trigger a deprecation message.


Migration
=========

Use the functions `getNativeFieldType()` and `getMetaFieldType()` provided by the DBMS specifics class.


.. index:: PHP-API, ext:dbal
