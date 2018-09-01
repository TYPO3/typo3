.. include:: ../../Includes.txt

====================================================
Deprecation: #84414 - BackendUtility::shortcutExists
====================================================

See :issue:`84414`

Description
===========

The PHP method :php:`TYPO3\CMS\Backend\Utility\BackendUtility::shortcutExists()` has been marked as deprecated and will be removed with TYPO3 v10.


Impact
======

Installations accessing the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances calling the method.


Migration
=========

Use an instance of :php:`TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository` and call method :php:`shortcutExists()` to get the same behavior.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
