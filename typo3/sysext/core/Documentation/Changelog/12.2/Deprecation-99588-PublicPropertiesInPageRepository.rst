.. include:: /Includes.rst.txt

.. _deprecation-99588-1673995832:

=========================================================
Deprecation: #99588 - Public Properties in PageRepository
=========================================================

See :issue:`99588`

Description
===========

One of TYPO3's main classes for fetching page records and content in the TYPO3 frontend is
:php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` previously known as `sys_page`.

This class has been around for a long time, and due to several improvements in TYPO3 v8
with Doctrine DBAL and in TYPO3 v9 with Context API and defining state via the Context API
and multiple instances of this class, it is not necessary to define public properties to modify
the behaviour of this class anymore.

For this reason, the following public properties are marked deprecated:

* :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->where_hid_del`
* :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->where_groupAccess`


Impact
======

Setting or reading these properties via PHP code in custom extensions will
trigger a PHP deprecation notice, however they continue to work in
TYPO3 v12.


Affected installations
======================

TYPO3 installations with custom extensions making use of these properties,
which is highly unlikely.


Migration
=========

It is recommended to migrate towards creating custom instances of this class with custom
contexts (for example, to show hidden records, or to use other workspace constraints),
as this is already done in TYPO3 Core since various versions.

If it is needed to build queries with the common restrictions, it is recommended to use
the API methods of this class, where most of the methods already have a
`$disableGroupAccessCheck` argument, or `enableFields()` which allows to return
common constraints, or to use the :php:`FrontendRestrictionContainer` when building
custom SQL queries with TYPO3's database layer directly.

.. index:: Frontend, PHP-API, FullyScanned, ext:core
