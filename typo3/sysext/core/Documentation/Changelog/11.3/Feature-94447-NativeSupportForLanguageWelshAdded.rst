.. include:: /Includes.rst.txt

=========================================================
Feature: #94447 - Native support for language Welsh added
=========================================================

See :issue:`94447`

Description
===========

TYPO3 now supports Welsh (historically known as "Cymbric"). Welsh is part
of the Celtic language family - is the official language in Wales, which is
part of the United Kingdom.

The ISO 639-1 code for Welsh is "cy", which is how TYPO3
is accessing the language internally.


Impact
======

It is now possible to

*  Fetch translated labels from translations.typo3.org / CrowdIn automatically
   within the TYPO3 Backend
*  Switch the backend interface to Welsh language
*  Create a new language in a site configuration using Welsh
*  Create translation files with the "cy" prefix (such as `cy.locallang.xlf`)
   to create your own labels

and TYPO3 will pick Welsh as a language just like any other supported language.

.. index:: Backend, Frontend, ext:core
