.. include:: /Includes.rst.txt

.. _feature-101151-1688113519:

==================================================
Feature: #101151 - Native enum DuplicationBehavior
==================================================

See :issue:`101151`

Description
===========

A new native backed enum :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior`
has been introduced for streamlined usage within:

* :php:`\TYPO3\CMS\Backend\Controller\File\FileController`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile`
* :php:`\TYPO3\CMS\Core\Resource\FileInterface`
* :php:`\TYPO3\CMS\Core\Resource\FileReference`
* :php:`\TYPO3\CMS\Core\Resource\Folder`
* :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOnlineMediaHelper`
* :php:`\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility`
* :php:`\TYPO3\CMS\Filelist\Controller\FileListController`
* :php:`\TYPO3\CMS\Impexp\Controller\ImportController`

Impact
======

The new :php:`\TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior` native backed
enum is meant to be a drop-in replacement for the former
:php:`\TYPO3\CMS\Core\Resource\DuplicationBehavior` class.

.. index:: Backend, NotScanned, ext:backend, ext:core, ext:filelist, ext:impexp
