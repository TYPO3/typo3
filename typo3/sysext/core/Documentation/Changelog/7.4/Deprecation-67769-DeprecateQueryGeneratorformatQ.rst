
.. include:: ../../Includes.txt

=========================================================
Deprecation: #67769 - Deprecate QueryGenerator::formatQ()
=========================================================

See :issue:`67769`

Description
===========

The method `QueryGenerator::formatQ()` which was used to format a query string, has been marked as deprecated.


Impact
======

All calls to the PHP method will throw a deprecation warning.


Affected Installations
======================

Instances which make use of `QueryGenerator::formatQ()`.


Migration
=========

No migration, use `htmlspecialchars` as alternative.


.. index:: PHP-API, Backend
