.. include:: /Includes.rst.txt

.. _feature-102032-1695805096:

=======================================
Feature: #102032 - Native enum FileType
=======================================

See :issue:`102032`

Description
===========

A new native backed enum :php:`\TYPO3\CMS\Core\Resource\FileType` is
introduced as a replacement for the public FILETYPE_* constants in
:php:`\TYPO3\CMS\Core\Resource\AbstractFile`

Impact
======

The new :php:`\TYPO3\CMS\Core\Resource\FileType` native backed enum is meant
to be a drop-in replacement for the former public :php:`FILETYPE_*` constants:

* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_UNKNOWN`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_TEXT`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_AUDIO`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_VIDEO`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_APPLICATION`

.. index:: Backend, FullyScanned, ext:core
