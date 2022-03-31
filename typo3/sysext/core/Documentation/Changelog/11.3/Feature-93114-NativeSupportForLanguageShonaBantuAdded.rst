.. include:: /Includes.rst.txt

=================================================================
Feature: #93114 - Native support for language Shona (Bantu) added
=================================================================

See :issue:`93114`

Description
===========

TYPO3 now supports Shona (Bantu language) - the language of the Shona
people of Zimbabwe - out of the box.

Shona is one of the most widely spoken Bantu languages
(`Shona on Wikipedia <https://en.wikipedia.org/wiki/Shona_language>`__).

The ISO 639-1 code for Shona is "sn", which is how TYPO3
is accessing the language internally.


Impact
======

It is now possible to

*  Fetch translated labels from translations.typo3.org / CrowdIn
   automatically within the TYPO3 Backend
*  Switch the Backend Interface to Shona language
*  Create a new language in a site configuration using Shona
*  Create translation files with the "sn" prefix (such as `sn.locallang.xlf`)
   to create your own labels

and TYPO3 will pick Shona as a language just like any other
supported language.

.. index:: Backend, Frontend, ext:core
