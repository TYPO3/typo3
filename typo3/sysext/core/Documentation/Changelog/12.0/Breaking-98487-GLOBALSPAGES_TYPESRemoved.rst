.. include:: /Includes.rst.txt

.. _breaking-98487-1664575125:

==================================================
Breaking: #98487 - $GLOBALS['PAGES_TYPES'] removed
==================================================

See :issue:`98487`

Description
===========

The global array :php:`PAGES_TYPES` has been removed in favor of a new registry
class containing the shared state.

Impact
======

Accessing or modifying :php:`$GLOBALS['PAGES_TYPES']` will have no effect anymore.

Affected installations
======================

TYPO3 installations with custom extensions creating custom TCA records or custom
Page Doktypes.

Migration
=========

Use the new :php:`TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry` class to register 
custom page types with their dependency what kind of records should be allowed for 
creation, or to read the information what record types are allowed on a
specific pages.doktype.

.. index:: PHP-API, FullyScanned, ext:core
