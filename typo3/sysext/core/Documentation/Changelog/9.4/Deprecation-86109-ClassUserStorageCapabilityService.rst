.. include:: /Includes.rst.txt

========================================================
Deprecation: #86109 - Class UserStorageCapabilityService
========================================================

See :issue:`86109`

Description
===========

Class :php:`TYPO3\CMS\Core\Resource\Service\UserStorageCapabilityService` has been
marked as deprecated and should not be used any longer.


Impact
======

This core internal class has been switched from a `TCA` :php:`userFunc` to a
:php:`renderType`.


Affected Installations
======================

Extensions probably never used this internal class, however, the extension
scanner will still find any usages.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, TCA, FullyScanned
