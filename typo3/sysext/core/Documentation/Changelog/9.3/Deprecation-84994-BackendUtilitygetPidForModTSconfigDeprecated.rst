.. include:: /Includes.rst.txt

============================================================
Deprecation: #84994 - BackendUtility::getPidForModTSconfig()
============================================================

See :issue:`84994`

Description
===========

Method :php:`TYPO3\CMS\backend\Utility\BackendUtility::getPidForModTSconfig()` has
been marked as deprecated and should not be used any longer.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions that call that method, even if is marked as internal. The extension scanner will find usages.


Migration
=========

Drop the method call and copy the one-liner implementation into consuming code.

.. index:: Backend, PHP-API, TSConfig, FullyScanned
