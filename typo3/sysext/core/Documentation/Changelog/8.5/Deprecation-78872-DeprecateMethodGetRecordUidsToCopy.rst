.. include:: /Includes.rst.txt

==================================================================================
Deprecation: #78872 - Deprecate method LocalizationController::getRecordUidsToCopy
==================================================================================

See :issue:`78872`

Description
===========

The method :php:`\TYPO3\CMS\Backend\Controller\Page\LocalizationController::getRecordUidsToCopy()`
is not used at any place in the TYPO3 core.


Impact
======

Calling the deprecated :php:`getRecordUidsToCopy()` method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using the mentioned method :php:`getRecordUidsToCopy()`.


Migration
=========

No migration available.

.. index:: Backend, PHP-API
