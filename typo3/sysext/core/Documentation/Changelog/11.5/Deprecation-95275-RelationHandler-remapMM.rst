.. include:: /Includes.rst.txt

================================================
Deprecation: #95275 - RelationHandler->remapMM()
================================================

See :issue:`95275`

Description
===========

Method :php:`TYPO3\CMS\Core\Database\RelationHandler->remapMM()` has been
marked as deprecated and will be removed with TYPO3 v12.


Impact
======

Calling above method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected installations
======================

It is highly unlikely instances are affected: The method handles a detail
related to workspaces publishing and is of little use in third party extensions.
The extension scanner will find usages as weak match.


Migration
=========

No direct substitution available, the method has been integrated into
:php:`TYPO3\CMS\Core\DataHandling\DataHandler`.

.. index:: PHP-API, FullyScanned, ext:core
