.. include:: /Includes.rst.txt

=========================================================
Breaking: #82893 - Remove global variable PARSETIME_START
=========================================================

See :issue:`82893`

Description
===========

The global variable :php:`$GLOBALS['PARSETIME_START']` can be removed, as it has been superseded by
:php:`$GLOBALS['TYPO3_MISC']['microtime_start']` for a long time already.


Impact
======

The variable is not available any more. If it is used it must be replaced (see Migration).


Affected Installations
======================

Installations that use the global variable :php:`$GLOBALS['PARSETIME_START']`.


Migration
=========

Use :php:`round($GLOBALS['TYPO3_MISC']['microtime_start'] * 1000)` if you need the same value as
:php:`$GLOBALS['PARSETIME_START']` previously contained.

.. index:: Frontend, PHP-API, FullyScanned
