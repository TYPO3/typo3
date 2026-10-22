.. include:: /Includes.rst.txt

.. _deprecation-80993:

================================================
Deprecation: #80993 - GeneralUtility::getUserObj
================================================

See :issue:`80993`

Description
===========

The method :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj()` has been marked as deprecated as it is a sole wrapper for
:php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()`.


Impact
======

Calling the method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using third-party extension that call this method.


Migration
=========

Use :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` instead, which acts as a simple drop-in replacement.

.. index:: PHP-API, FullyScanned
