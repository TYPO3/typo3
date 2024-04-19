.. include:: /Includes.rst.txt

.. _feature-103372:

=================================================================
Feature: #103372 - Native support for language Irish Gaelic added
=================================================================

See :issue:`103372`

Description
===========

TYPO3 now supports Irish Gaelic. Irish Gaelic language is spoken in Ireland.

The ISO 639-1 code for Irish Gaelic is "ga", which is how TYPO3
accesses the language internally.


Impact
======

It is now possible to

*  Fetch translated labels from translations.typo3.org / CrowdIn automatically
   within the TYPO3 backend.
*  Switch the backend interface to Irish Gaelic language.
*  Create a new language in a site configuration using Irish Gaelic.
*  Create translation files with the "ga" prefix (such as `ga.locallang.xlf`)
   to create your own labels.

TYPO3 will pick Irish Gaelic as a language just like any other supported language.

.. index:: Backend, Frontend, ext:core
