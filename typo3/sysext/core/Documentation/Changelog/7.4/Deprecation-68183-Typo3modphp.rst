
.. include:: ../../Includes.txt

===================================
Deprecation: #68183 - typo3/mod.php
===================================

See :issue:`68183`

Description
===========

The mod.php entry-point has been marked as deprecated. All Backend Module requests in the Backend using the Module Url API are
not affected as they automatically use index.php.


Impact
======

All extensions directly linking to typo3/mod.php will throw a deprecation warning.


Affected Installations
======================

Installations with custom extensions that call typo3/mod.php without using proper API calls from `BackendUtility`.


Migration
=========

Use `BackendUtility::getModuleUrl()`.


.. index:: PHP-API, Backend
