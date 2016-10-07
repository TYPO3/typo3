
.. include:: ../../Includes.txt

===============================================================
Deprecation: #74022 - GraphicalFunctions->prependAbsolutePath()
===============================================================

See :issue:`74022`

Description
===========

The method `GraphicalFunctions->prependAbsolutePath()` has been marked as deprecated.


Impact
======

Calling the method above will trigger a deprecation log entry.


Affected Installations
======================

Any installation with custom extensions that use GraphicalFunctions and the method directly.


Migration
=========

Use `GeneralUtility::getFileAbsFileName()` instead.

.. index:: PHP-API
