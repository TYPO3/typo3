.. include:: /Includes.rst.txt

.. _feature-103331:

============================================================
Feature: #103331 - Native support for language Maltese added
============================================================

See :issue:`103331`

Description
===========

TYPO3 now supports Maltese. Maltese language is spoken in Malta.

The ISO 639-1 code for Maltese is "mt", which is how TYPO3
accesses the language internally.


Impact
======

It is now possible to

*  Fetch translated labels from translations.typo3.org / CrowdIn automatically
   within the TYPO3 backend.
*  Switch the backend interface to Maltese language.
*  Create a new language in a site configuration using Maltese.
*  Create translation files with the "mt" prefix (such as `mt.locallang.xlf`)
   to create your own labels.

TYPO3 will pick Maltese as a language just like any other supported language.

.. index:: Backend, Frontend, ext:core
