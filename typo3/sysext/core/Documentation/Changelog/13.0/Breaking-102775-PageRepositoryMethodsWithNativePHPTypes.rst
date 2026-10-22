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

- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getPage()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getPage_noCheck()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getPageOverlay()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getPagesOverlay()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->checkRecord()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getRawRecord()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->enableFields()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getMultipleGroupsWhereClause()`
- :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->versionOL()`


Impact
======

Calling the affected methods now requires the passed arguments to be
of the specified PHP type. Otherwise a PHP TypeError is triggered.


Affected installations
======================

TYPO3 installations with third-party extensions utilizing the
:php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` PHP class.


Migration
=========

Extension authors need to adapt their PHP code to use ensure passed
arguments are of the required PHP type when calling corresponding methods
of the :php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` PHP class. Using proper type casts would is
a possible migration strategy.

.. index:: PHP-API, NotScanned, ext:core
