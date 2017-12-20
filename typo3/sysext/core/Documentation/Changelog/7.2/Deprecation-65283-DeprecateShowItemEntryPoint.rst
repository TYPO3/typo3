
.. include:: ../../Includes.txt

=====================================================
Deprecation: #65283 - Deprecate show item entry point
=====================================================

See :issue:`65283`

Description
===========

The following entry point has been marked as deprecated:

* typo3/show_item.php


Impact
======

Using this entry point in a backend module will throw a deprecation message.


Migration
=========

Use `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()` instead with the according module name.

`typo3/show_item.php` will have to be refactored to `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('show_item')`


.. index:: PHP-API, Backend
