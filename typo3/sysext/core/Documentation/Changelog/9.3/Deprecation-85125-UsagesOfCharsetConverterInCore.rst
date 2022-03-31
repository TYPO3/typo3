.. include:: /Includes.rst.txt

==================================================================
Deprecation: #85125 - Deprecate usages of CharsetConverter in core
==================================================================

See :issue:`85125`

Description
===========

The following method has been marked as deprecated:

- :php:`TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver->getCharsetConversion()`

The following public properties have been deprecated:

- :php:`TYPO3\CMS\IndexedSearch\Lexer->csObj`
- :php:`TYPO3\CMS\IndexedSearch\Indexer->csObj`


Impact
======

Calling the method or accessing any of the properties will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 Installations with custom FAL drivers or special handling for indexed search extending the
Lexer functionality.


Migration
=========

Check the extension scanner if the site is affected and instantiate CharsetConverter directly in the
callers' code.

.. index:: PHP-API, FullyScanned
