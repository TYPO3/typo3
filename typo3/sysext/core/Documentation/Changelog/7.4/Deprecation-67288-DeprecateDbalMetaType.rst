
.. include:: ../../Includes.txt

==========================================================================
Deprecation: #67288 - Deprecate Dbal\DatabaseConnection::MetaType() method
==========================================================================

See :issue:`67288`

Description
===========

The following public function has been marked as deprecated as the bugfix requires a signature change:

* `Dbal\DatabaseConnection->MetaType()`


Impact
======

Using this function will throw a deprecation warning. Due to missing information the field type cache will
be bypassed and the DBMS will be queried for the necessary information on each call.


Migration
=========

Switch to `getMetadata()` and the field name for which you need the ADOdb MetaType information.


.. index:: PHP-API, Database, ext:dbal
