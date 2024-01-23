.. include:: /Includes.rst.txt

.. _breaking-102775-1704711591:

================================================================
Breaking: #102775 - PageRepository methods with native PHP types
================================================================

See :issue:`102775`

Description
===========

Various methods in of the main TYPO3 Core classes
:php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository`
now have native PHP types in their method signature, requiring the caller code
to use exactly the required PHP types for the corresponding method arguments.

The following methods are affected:

- :php:`PageRepository->getPage()`
- :php:`PageRepository->getPage_noCheck()`
- :php:`PageRepository->getPageOverlay()`
- :php:`PageRepository->getPagesOverlay()`
- :php:`PageRepository->checkRecord()`
- :php:`PageRepository->getRawRecord()`
- :php:`PageRepository->enableFields()`
- :php:`PageRepository->getMultipleGroupsWhereClause()`
- :php:`PageRepository->versionOL()`


Impact
======

Calling the affected methods now requires the passed arguments to be
of the specified PHP type. Otherwise a PHP TypeError is triggered.


Affected installations
======================

TYPO3 installations with third-party extensions utilizing the
:php:`PageRepository` PHP class.


Migration
=========

Extension authors need to adapt their PHP code to use ensure passed
arguments are of the required PHP type when calling corresponding methods
of the :php:`PageRepository` PHP class. Using proper type casts would is
a possible migration strategy.

.. index:: PHP-API, NotScanned, ext:core
