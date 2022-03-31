
.. include:: /Includes.rst.txt

============================================================================================
Deprecation: #73067 - Deprecate GeneralUtility::requireOnce and  GeneralUtility::requireFile
============================================================================================

See :issue:`73067`

Description
===========

The following methods from `TYPO3\CMS\Core\Utility\GeneralUtility` have been
marked as deprecated.

`GeneralUtility::requireOnce()`
`GeneralUtility::requireFile()`


Impact
======

Using aforementioned methods will trigger a deprecation log entry.


Affected Installations
======================

Instances which use one of the aforementioned methods.


Migration
=========

Use native require_once if needed, e.g. if autoloading does not work.

.. index:: PHP-API
