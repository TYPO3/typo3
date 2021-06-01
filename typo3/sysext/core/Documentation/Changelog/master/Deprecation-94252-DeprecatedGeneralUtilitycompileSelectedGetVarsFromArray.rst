.. include:: ../../Includes.txt

================================================================================
Deprecation: #94252 - Deprecated GeneralUtility::compileSelectedGetVarsFromArray
================================================================================

See :issue:`94252`

Description
===========

In our effort to reduce usages of :php:`GeneralUtility::_GP()`, the
:php:`GeneralUtility` method :php:`compileSelectedGetVarsFromArray` is
deprecated, since it internally calls :php:`GeneralUtility::_GP()` instead
of accessing the PSR-7 Request. The method was furthermore only used once
in the core since it's internal logic can easily be implemented on a case
by case basis.

Impact
======

Calling the method will log a deprecation warning and the method will
be dropped with TYPO3 v12.


Affected Installations
======================

All TYPO3 installations calling this method in custom code. The extension
scanner will find all usages as strong match.


Migration
=========

All usages of the method in custom extension code have to be replaced
with a custom implementation, preferably using the PSR-7 Request.

See: :php:`EditDocumentController->compileStoreData()` for an example
on how such migration could look like.

.. index:: PHP-API, FullyScanned, ext:core
