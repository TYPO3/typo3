
.. include:: ../../Includes.txt

============================================================
Breaking: #67646 - PHP library inclusion in frontend removed
============================================================

See :issue:`67646`

Description
===========

The PHP library inclusion into the TYPO3 Frontend has been removed without substitution.
Previously it was used to include plain PHP scripts during the Frontend request.

The method `PageGenerator::getIncFiles()` has been removed.


Impact
======

The TypoScript options `config.includeLibrary` and `config.includeLibs` have no effect anymore.
Any calls to `PageGenerator::getIncFiles()` will result in a fatal error.


Affected Installations
======================

Any installation using the TypoScript options named above.
Any third party code using the method named above.


Migration
=========

Use hooks during the Frontend set up to execute custom PHP code.
