.. include:: ../../Includes.txt

=========================================================================
Deprecation: #80514 - GraphicalFunctions->tempPath and createTempSubDir()
=========================================================================

See :issue:`80514`

Description
===========

The method :php:`GraphicalFunctions->createTempSubDir()` and the property
:php:`GraphicalFunctions->tempPath` have been marked as deprecated.


Impact
======

Calling the method above will trigger a deprecation log entry.


Affected Installations
======================

Any instance with custom extensions extending the PHP class GraphicalFunctions.


Migration
=========

Use :php:`GeneralUtility::mkdir_deep()` with the full path (including the PHP constant `PATH_site`)
directly.

.. index:: PHP-API
