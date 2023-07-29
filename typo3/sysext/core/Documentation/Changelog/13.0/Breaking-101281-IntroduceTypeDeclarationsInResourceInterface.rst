.. include:: /Includes.rst.txt

.. _breaking-101281-1688708590:

====================================================================
Breaking: #101281 - Introduce type declarations in ResourceInterface
====================================================================

See :issue:`101281`

Description
===========

The following methods of interface
:php:`\TYPO3\CMS\Core\Resource\ResourceInterface` have been given return
type declarations:

..  code-block:: php

    public function getIdentifier(): string;
    public function getName(): string;
    public function getStorage(): ResourceStorage;
    public function getHashedIdentifier(): string;
    public function getParentFolder(): FolderInterface;


Impact
======

This affects many classes due to the following implementation
rules:

-   :php:`\TYPO3\CMS\Core\Resource\Folder`, because it implements
    :php:`\TYPO3\CMS\Core\Resource\FolderInterface` which extends
    :php:`\TYPO3\CMS\Core\Resource\ResourceInterface`

-   :php:`\TYPO3\CMS\Core\Resource\FileReference`, and
    :php:`\TYPO3\CMS\Core\Resource\AbstractFile` because both implement
    :php:`\TYPO3\CMS\Core\Resource\FileInterface` which extends
    :php:`\TYPO3\CMS\Core\Resource\ResourceInterface`

-   :php:`\TYPO3\CMS\Core\Resource\File` and
    :php:`\TYPO3\CMS\Core\Resource\ProcessedFile`
    because both extend :php:`\TYPO3\CMS\Core\Resource\AbstractFile`

In consequence, the following methods are affected:

-   :php:`\TYPO3\CMS\Core\Resource\Folder::getIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\Folder::getName()`
-   :php:`\TYPO3\CMS\Core\Resource\Folder::getStorage()`
-   :php:`\TYPO3\CMS\Core\Resource\Folder::getHashedIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\Folder::getParentFolder()`
-   :php:`\TYPO3\CMS\Core\Resource\FileReference::getIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\FileReference::getName()`
-   :php:`\TYPO3\CMS\Core\Resource\FileReference::getStorage()`
-   :php:`\TYPO3\CMS\Core\Resource\FileReference::getHashedIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\FileReference::getParentFolder()`
-   :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getName()`
-   :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getStorage()`
-   :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getHashedIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getParentFolder()`
-   :php:`\TYPO3\CMS\Core\Resource\File::getIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\File::getName()`
-   :php:`\TYPO3\CMS\Core\Resource\File::getStorage()`
-   :php:`\TYPO3\CMS\Core\Resource\File::getHashedIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\File::getParentFolder()`
-   :php:`\TYPO3\CMS\Core\Resource\ProcessedFile::getIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\ProcessedFile::getName()`
-   :php:`\TYPO3\CMS\Core\Resource\ProcessedFile::getStorage()`
-   :php:`\TYPO3\CMS\Core\Resource\ProcessedFile::getHashedIdentifier()`
-   :php:`\TYPO3\CMS\Core\Resource\ProcessedFile::getParentFolder()`


Affected installations
======================

Affected installations are those which either implement the :php:`ResourceInterface`
directly (very unlikely) or those that extend any of mentioned implementations
(Core classes).

The usage (the API) of those implementation itself has not changed!


Migration
=========

Use the same return type declarations as :php:`ResourceInterface` does.

.. index:: FAL, PHP-API, NotScanned, ext:core
