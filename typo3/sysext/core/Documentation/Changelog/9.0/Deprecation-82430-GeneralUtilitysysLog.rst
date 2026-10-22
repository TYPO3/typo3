.. include:: /Includes.rst.txt

.. _deprecation-82430:

============================================
Deprecation: #82430 - GeneralUtility::sysLog
============================================

See :issue:`82430`

Description
===========

The :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog` API is superseded by the Logging API.

Therefore the methods :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog` and :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::initSysLog` have been marked as deprecated.


Impact
======

Calling these methods triggers a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation having third party extensions using these methods.


Migration
=========

Replace the :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog` calls with direct calls to the Logging API.

.. index:: PHP-API, NotScanned
