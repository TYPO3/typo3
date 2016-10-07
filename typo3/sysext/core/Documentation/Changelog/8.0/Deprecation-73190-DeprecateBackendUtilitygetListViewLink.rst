
.. include:: ../../Includes.txt

=================================================================
Deprecation: #73190 - Deprecate BackendUtility::getListViewLink()
=================================================================

See :issue:`73190`

Description
===========

The method `BackendUtility::getListViewLink()` has been marked as deprecated and will be removed in TYPO3 v9.


Impact
======

Calling the method above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with a third-party extension calling this method directly.


Migration
=========

Use the `IconFactory` and `BackendUtility::getModuleUrl()` API methods directly instead to create links.

.. index:: PHP-API, Backend
