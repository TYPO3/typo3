
.. include:: ../../Includes.txt

=================================================
Deprecation: #65290 - Deprecate dummy entry point
=================================================

See :issue:`65290`

Description
===========

The following entry point has been marked as deprecated:

* typo3/dummy.php


Impact
======

Using this entry point in a backend module will throw a deprecation message.


Migration
=========

Use `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()` instead with the according module name.

`typo3/dummy.php` will have to be refactored to `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('dummy')`
