
.. include:: ../../Includes.txt

====================================
Deprecation: #68141 - typo3/ajax.php
====================================

See :issue:`68141`

Description
===========

The ajax.php entry-point has been marked as deprecated. All AJAX requests in the Backend using the Ajax API are
not affected as they automatically use index.php.


Impact
======

All extensions directly linking to typo3/ajax.php will throw a deprecation warning.


Affected Installations
======================

Installations with custom extensions that call typo3/ajax.php without using proper API calls from `BackendUtility`.


Migration
=========

Use `BackendUtility::getAjaxUrl()`.
