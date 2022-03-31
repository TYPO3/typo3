.. include:: /Includes.rst.txt

============================================
Deprecation: #82430 - GeneralUtility::sysLog
============================================

See :issue:`82430`

Description
===========

The :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog` API is superseded by the Logging API.

Therefore the methods :php:`GeneralUtility::sysLog` and :php:`GeneralUtility::initSysLog` have been marked as deprecated.


Impact
======

Calling these methods triggers a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation having third party extensions using these methods.


Migration
=========

Replace the :php:`GeneralUtility::sysLog` calls with direct calls to the Logging API.

.. index:: PHP-API, NotScanned
