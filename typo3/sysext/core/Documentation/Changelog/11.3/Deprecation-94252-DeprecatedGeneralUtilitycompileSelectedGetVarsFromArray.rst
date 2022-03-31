.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #94252 - GeneralUtility::compileSelectedGetVarsFromArray
=====================================================================

See :issue:`94252`

Description
===========

In our effort to reduce usages of :php:`GeneralUtility::_GP()`, the
:php:`GeneralUtility` method :php:`compileSelectedGetVarsFromArray` is
deprecated, since it internally calls :php:`GeneralUtility::_GP()` instead
of accessing the PSR-7 Request. The method was furthermore only used once
in the Core since it's internal logic can easily be implemented on a case
by case basis.

Impact
======

Using the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All TYPO3 installations calling this method in custom code are affected.
The extension scanner will find such usages as strong match.


Migration
=========

Usages of the method in custom extension code have to be replaced
with a custom implementations, preferably using the PSR-7 Request.

See: :php:`\TYPO3\CMS\Backend\Controller\EditDocumentController->compileStoreData()`
for an example on how such migration could look like.

.. index:: PHP-API, FullyScanned, ext:core
