.. include:: /Includes.rst.txt

.. _breaking-101398-1689861816:

======================================================================
Breaking: #101398 - Remove leftover $fetchAllFields in RelationHandler
======================================================================

See :issue:`101398`

Description
===========

The :php:`\TYPO3\CMS\Core\Database\RelationHandler` had an unused property
:php:`$fetchAllFields` since TYPO3 v11.5.0. The related method
:php:`setFetchAllFields()` has been removed with it.


Impact
======

Custom extensions calling :php:`\TYPO3\CMS\Core\Database\RelationHandler->setFetchAllFields()`
will result in a PHP Fatal error.


Affected installations
======================

All installations with custom extensions calling
:php:`\TYPO3\CMS\Core\Database\RelationHandler->setFetchAllFields()`.


Migration
=========

Remove the affected line of code. This method has had no effect since
TYPO3 v11.5.0.

.. index:: PHP-API, FullyScanned, ext:core
