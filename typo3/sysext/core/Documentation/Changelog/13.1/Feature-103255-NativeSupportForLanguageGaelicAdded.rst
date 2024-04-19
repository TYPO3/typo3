.. include:: /Includes.rst.txt

.. _feature-103255:

====================================================================
Feature: #103255 - Native support for language Scottish Gaelic added
====================================================================

See :issue:`103255`

Description
===========

TYPO3 now supports Scottish Gaelic. Scottish Gaelic language is spoken in Scotland.

The ISO 639-1 code for Scottish Gaelic is "gd", which is how TYPO3
accesses the language internally.


Impact
======

It is now possible to

*  Fetch translated labels from translations.typo3.org / CrowdIn automatically
   within the TYPO3 backend.
*  Switch the backend interface to Scottish Gaelic language.
*  Create a new language in a site configuration using Scottish Gaelic.
*  Create translation files with the "gd" prefix (such as `gd.locallang.xlf`)
   to create your own labels.

TYPO3 will pick Scottish Gaelic as a language just like any other supported language.

.. index:: Backend, Frontend, ext:core
