.. include:: /Includes.rst.txt

============================================================
Deprecation: #82445 - Page translation related functionality
============================================================

See :issue:`82445`

Description
===========

With the merge of row content from table `pages_language_overlay` into `pages`
various core functionality has been deprecated.

Methods:

* :php:`TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->getTranslationTable()`
* :php:`TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->isTranslationInOwnTable()`
* :php:`TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->foreignTranslationTable()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getOriginalTranslationTable()`

Additionally, the automatic TCA migration performed by the TYPO3 bootstrap now merges flags of type
:php:`['columns']['someField']['config']['behaviour']['allowLanguageSynchronization'] from
table `pages_language_overlay` into `pages`.


Impact
======

A deprecation warning is thrown calling one of the above methods and if the TCA migration
changes the `allowLanguageSynchronization` flag.


Affected Installations
======================

Instances using the above methods or TCA configuration. The install tool extension scanner will
find affected extensions and the TCA migrations check of the install tool shows applied TCA migrations.


Migration
=========

The functionality to have language overlays records in a different table than the table the default language
records are in has been removed. It is safe to no longer check for this and use `pages` for page language
overlay records directly.

.. index:: Backend, PHP-API, TCA, FullyScanned
