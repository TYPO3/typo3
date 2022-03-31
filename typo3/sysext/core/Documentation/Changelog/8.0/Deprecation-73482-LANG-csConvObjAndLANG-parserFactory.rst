
.. include:: /Includes.rst.txt

===============================================================
Deprecation: #73482 - $LANG->csConvObj and $LANG->parserFactory
===============================================================

See :issue:`73482`

Description
===========

The properties of LanguageService (also known as `$GLOBALS[LANG]`) csConvObj and parserFactory
have been marked as deprecated. Since these three PHP classes are not dependent on each other, they
can be decoupled. The getter method `getParserFactory()` has thus been marked as deprecated as well.


Impact
======

These properties will be removed in TYPO3 v9. Calling `LanguageService->getParserFactory()` will trigger a
deprecation log entry.


Affected Installations
======================

Installations with custom extension accessing the LanguageService properties and method above.


Migration
=========

Instantiate CharsetConverter (csConvObj) and LocalizationFactory (parserFactory) via `GeneralUtility::makeInstance`
directly if needed, as they are Singleton objects and then fetched from the General Utility Object container
functionalities.

.. index:: PHP-API
