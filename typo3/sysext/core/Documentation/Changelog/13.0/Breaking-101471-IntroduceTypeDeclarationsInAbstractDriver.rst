.. include:: /Includes.rst.txt

.. _breaking-101471-1690531810:

=================================================================
Breaking: #101471 - Introduce type declarations in AbstractDriver
=================================================================

See :issue:`101471`

Description
===========

Return and param type declarations have been introduced for all methods and method
stubs of :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver` and
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver`


Impact
======

In consequence, all classes, extending any of those abstract classes and overriding
any of those affected methods need to reflect those changes and add the same return
and param type declarations.

Affected methods are:

- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::isValidFilename()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::getTemporaryPathForFile()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::canonicalizeAndCheckFilePath()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::canonicalizeAndCheckFileIdentifier()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::canonicalizeAndCheckFolderIdentifier()`

- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver::isPathValid()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver::canonicalizeAndCheckFilePath()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver::canonicalizeAndCheckFileIdentifier()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver::canonicalizeAndCheckFolderIdentifier()`


Affected installations
======================

Installations that extend any of those abstract classes might be affected.


Migration
=========

Add the same param and return type declarations the interface does.


.. index:: FAL, PHP-API, NotScanned, ext:core
