
.. include:: ../../Includes.txt

======================================================================
Deprecation: #69269 - Deprecate BackendUtility::getPathType_web_nonweb
======================================================================

See :issue:`69269`

Description
===========

Method `getPathType_web_nonweb()` of class `TYPO3\CMS\Backend\Utility\BackendUtility` has been marked as deprecated.


Impact
======

The method should not be used any longer and will be removed with TYPO3 CMS 8.


Affected Installations
======================

The method is unused in the core since at least TYPO3 CMS 6.2.
Searching for the string `getPathType_web_nonweb` may reveal possible usages.


Migration
=========

Use path functions from `TYPO3\CMS\Core\Utility\PathUtility`.


.. index:: PHP-API, Backend
