.. include:: /Includes.rst.txt

.. _breaking-101459-1690461283:

===============================================================
Breaking: #101459 - Introduce type declarations in AbstractFile
===============================================================

See :issue:`101459`

Description
===========

Return and param type declarations have been introduced for all methods of
of :php:`\TYPO3\CMS\Core\Resource\AbstractFile`.

Impact
======

In consequence, all classes that extend of :php:`\TYPO3\CMS\Core\Resource\AbstractFile`
need to reflect those changes and add the same return and param type declarations.

Affected methods are:

- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getProperties()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getUid()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getType()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::exists()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::setStorage()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::setIdentifier()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getCombinedIdentifier()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::setDeleted()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::isDeleted()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::copyTo()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::moveTo()`
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile::updateProperties()`

Both core classes :php:`\TYPO3\CMS\Core\Resource\ProcessedFile` and
:php:`\TYPO3\CMS\Core\Resource\File` extend `AbstractFile`, therefore those methods
have also been adjusted for both classes.

Affected installations
======================

Installations that extend

- :php:`\TYPO3\CMS\Core\Resource\AbstractFile` or
- :php:`\TYPO3\CMS\Core\Resource\ProcessedFile` or
- :php:`\TYPO3\CMS\Core\Resource\File`

and which override at least one of those mentioned methods are affected.

Migration
=========

Introduce the same type declarations as the parent class.

.. index:: FAL, PHP-API, NotScanned, ext:core
