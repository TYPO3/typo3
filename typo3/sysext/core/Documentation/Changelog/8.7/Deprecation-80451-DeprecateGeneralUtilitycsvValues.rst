.. include:: /Includes.rst.txt

=========================================================
Deprecation: #80451 - Deprecate GeneralUtility::csvValues
=========================================================

See :issue:`80451`

Description
===========

The method :php:`GeneralUtility::csvValues()` has been marked as deprecated.


Impact
======

Calling the deprecated methods will trigger a deprecation log entry.


Migration
=========

Use the new method :php:`CsvUtility::csvValues()`


.. index:: Backend, PHP-API
