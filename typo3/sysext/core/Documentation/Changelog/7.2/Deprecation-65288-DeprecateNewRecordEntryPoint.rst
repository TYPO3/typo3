
.. include:: ../../Includes.txt

========================================================
Deprecation: #65288 - Deprecate "new record" entry point
========================================================

See :issue:`65288`

Description
===========

The following entry point has been marked as deprecated:

* typo3/db_new.php


Impact
======

Using this entry point in a backend module will throw a deprecation message.


Migration
=========

Use `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()` instead with the according module name.

`typo3/db_new.php` will have to be refactored to `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('db_new')`
