
.. include:: ../../Includes.txt

===============================================
Breaking: #73504 - Make TimeTracker a singleton
===============================================

See :issue:`73504`

Description
===========

The class `\TYPO3\CMS\Core\TimeTracker\TimeTracker` has been marked as singleton and is no longer stored in `$GLOBALS['TT']`.


Impact
======

Using methods of `$GLOBALS['TT']` will result in a fatal error.


Affected Installations
======================

All installations or 3rd party extensions using `$GLOBALS['TT']`.


Migration
=========

Use `\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\TimeTracker\TimeTracker::class)` instead.

.. index:: PHP-API
