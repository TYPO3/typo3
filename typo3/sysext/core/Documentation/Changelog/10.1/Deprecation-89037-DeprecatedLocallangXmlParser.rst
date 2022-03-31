.. include:: /Includes.rst.txt

===================================================
Deprecation: #89037 - Deprecated LocallangXmlParser
===================================================

See :issue:`89037`

Description
===========

The :php:`\TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser` has been used to parse localization files based on the custom ll-XML format ("ll" refers to "locallang").
Since TYPO3 version 4.6 XLIFF is being used and therefore the previous support for locallang-XML files has been marked as deprecated.

Impact
======

Calling :php:`\TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser` or using locallang-XML files will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All installations using extensions using ll-XML localization files.


Migration
=========

Migrate all XML files to the XLIFF standard.

.. index:: Backend, Frontend, FullyScanned, ext:core
