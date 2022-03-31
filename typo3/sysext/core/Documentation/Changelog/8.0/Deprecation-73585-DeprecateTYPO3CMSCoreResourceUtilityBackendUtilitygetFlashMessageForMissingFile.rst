
.. include:: /Includes.rst.txt

=============================================================================================================
Deprecation: #72585 - Deprecate TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile
=============================================================================================================

See :issue:`72585`

Description
===========

`TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile` has been marked as deprecated.


Impact
======

Calling this method will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using `getFlashMessageForMissingFile` method within an extension or third-party code.


Migration
=========

No migration

.. index:: PHP-API
