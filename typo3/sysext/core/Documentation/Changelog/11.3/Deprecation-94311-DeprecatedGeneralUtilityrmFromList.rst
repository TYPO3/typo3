.. include:: /Includes.rst.txt

================================================
Deprecation: #94311 - GeneralUtility::rmFromList
================================================

See :issue:`94311`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList()` has not
been used in the Core since v10. The method has now been deprecated
in :php:`\TYPO3\CMS\Core\Utility\GeneralUtility` and will be removed in
TYPO3 v12.

Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All TYPO3 installations calling this method in custom code. The extension
scanner will find all such usages as strong match.


Migration
=========

Replace all usages of the method in your extension code.

.. index:: PHP-API, FullyScanned, ext:core
