.. include:: /Includes.rst.txt

.. _deprecation-102032-1695805007:

=========================================================
Deprecation: #102032 - AbstractFile::FILETYPE_* constants
=========================================================

See :issue:`102032`

Description
===========

The :php:`int` constants file types have been marked as deprecated:

* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_UNKNOWN`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_TEXT`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_AUDIO`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_VIDEO`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_APPLICATION`

and will be removed in TYPO3 v14.0.

Impact
======

Using :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_*` constants
will be detected by the extension scanner.


Affected installations
======================

All installations with third-party extensions using
:php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_*` constants
are affected.


Migration
=========

Migrate all usages to use the new enum :php:`\TYPO3\CMS\Core\Resource\FileType`
as follows:

* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_UNKNOWN` -> :php:`\TYPO3\CMS\Core\Resource\FileType::UNKNOWN->value`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_TEXT` -> :php:`\TYPO3\CMS\Core\Resource\FileType::TEXT->value`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE` -> :php:`\TYPO3\CMS\Core\Resource\FileType::IMAGE->value`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_AUDIO` -> :php:`\TYPO3\CMS\Core\Resource\FileType::AUDIO->value`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_VIDEO` -> :php:`\TYPO3\CMS\Core\Resource\FileType::VIDEO->value`
* :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_APPLICATION` -> :php:`\TYPO3\CMS\Core\Resource\FileType::APPLICATION->value`

.. index:: Backend, FullyScanned, ext:core
