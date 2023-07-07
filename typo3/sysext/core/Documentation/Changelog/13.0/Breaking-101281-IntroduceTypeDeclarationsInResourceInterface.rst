.. include:: /Includes.rst.txt

.. _breaking-101281-1688708590:

====================================================================
Breaking: #101281 - Introduce type declarations in ResourceInterface
====================================================================

See :issue:`101281`

Description
===========

The following methods of interface :php:`ResourceInterface` have gotten return type declarations:

..  code:php:

    public function getIdentifier(): string;
    public function getName(): string;
    public function getStorage(): ResourceStorage;
    public function getHashedIdentifier(): string;
    public function getParentFolder(): FolderInterface;


Impact
======

This affects many classes due to the following implementation
rules:

- \TYPO3\CMS\Core\Resource\Folder, because it implements
  \TYPO3\CMS\Core\Resource\FolderInterface which extends
  \TYPO3\CMS\Core\Resource\ResourceInterface

- \TYPO3\CMS\Core\Resource\FileReference, and
  \TYPO3\CMS\Core\Resource\AbstractFile because both implement
  \TYPO3\CMS\Core\Resource\FileInterface which extends
  \TYPO3\CMS\Core\Resource\ResourceInterface

- \TYPO3\CMS\Core\Resource\File and \TYPO3\CMS\Core\Resource\ProcessedFile
  because both extend \TYPO3\CMS\Core\Resource\AbstractFile

In consequence, the following methods are affected:

- \TYPO3\CMS\Core\Resource\Folder::getIdentifier()
- \TYPO3\CMS\Core\Resource\Folder::getName()
- \TYPO3\CMS\Core\Resource\Folder::getStorage()
- \TYPO3\CMS\Core\Resource\Folder::getHashedIdentifier()
- \TYPO3\CMS\Core\Resource\Folder::getParentFolder()
- \TYPO3\CMS\Core\Resource\FileReference::getIdentifier()
- \TYPO3\CMS\Core\Resource\FileReference::getName()
- \TYPO3\CMS\Core\Resource\FileReference::getStorage()
- \TYPO3\CMS\Core\Resource\FileReference::getHashedIdentifier()
- \TYPO3\CMS\Core\Resource\FileReference::getParentFolder()
- \TYPO3\CMS\Core\Resource\AbstractFile::getIdentifier()
- \TYPO3\CMS\Core\Resource\AbstractFile::getName()
- \TYPO3\CMS\Core\Resource\AbstractFile::getStorage()
- \TYPO3\CMS\Core\Resource\AbstractFile::getHashedIdentifier()
- \TYPO3\CMS\Core\Resource\AbstractFile::getParentFolder()
- \TYPO3\CMS\Core\Resource\File::getIdentifier()
- \TYPO3\CMS\Core\Resource\File::getName()
- \TYPO3\CMS\Core\Resource\File::getStorage()
- \TYPO3\CMS\Core\Resource\File::getHashedIdentifier()
- \TYPO3\CMS\Core\Resource\File::getParentFolder()
- \TYPO3\CMS\Core\Resource\ProcessedFile::getIdentifier()
- \TYPO3\CMS\Core\Resource\ProcessedFile::getName()
- \TYPO3\CMS\Core\Resource\ProcessedFile::getStorage()
- \TYPO3\CMS\Core\Resource\ProcessedFile::getHashedIdentifier()
- \TYPO3\CMS\Core\Resource\ProcessedFile::getParentFolder()


Affected installations
======================

Affected installations are those which either implement the ResourceInterface directly (very unlikely) or those that extend any of mentioned implementations (core classes).

The usage (the API) of those implementation itself has not changed!


Migration
=========

Use the same return type declarations as ResourceInterface does.

.. index:: FAL, PHP-API, NotScanned, ext:core
