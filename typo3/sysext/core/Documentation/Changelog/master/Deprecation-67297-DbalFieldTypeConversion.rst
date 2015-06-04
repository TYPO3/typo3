========================================================
Deprecation: #67297 - MySQL / DBMS field type conversion
========================================================

Description
===========

The Dbal\DatabaseConnection provides generic functions that translate between native MySQL field types
and ADOdb meta field types. The generic functions ``MySQLActualType()`` and ``MySQLMetaType`` are
deprecated and should not be used any longer.


Impact
======

Although these are public functions the use was probably limited to the DBAL Extension.


Migration
=========

Use the functions ``getNativeFieldType()`` and ``getMetaFieldType()`` provided by the DBMS specifics class.
