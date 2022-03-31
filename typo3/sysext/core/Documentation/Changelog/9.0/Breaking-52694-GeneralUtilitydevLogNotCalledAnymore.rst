.. include:: /Includes.rst.txt

==============================================================
Breaking: #52694 - GeneralUtility::devLog() not called anymore
==============================================================

See :issue:`52694`

Description
===========

:php:`TYPO3\CMS\Core\Utility\GeneralUtility::devLog()` is deprecated. Therefore the Core does not call this function
anymore. Instead the Logging API is used to write log data.

The option to write the deprecation log to the devLog has been removed.

Impact
======

Log data can be filtered through the writer configuration for the Logging API.
Registered devLog extensions are not triggered anymore by the Core.


Migration
=========

Add a custom writer configuration to retrieve devLog entries. These are mostly of level Info and Notice.

.. index:: PHP-API, NotScanned
