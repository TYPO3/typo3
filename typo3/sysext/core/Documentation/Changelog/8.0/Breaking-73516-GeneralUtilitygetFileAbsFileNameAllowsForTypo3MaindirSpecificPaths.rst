
.. include:: ../../Includes.txt

==============================================================================================
Breaking: #73516 - GeneralUtility::getFileAbsFileName allows for typo3/ maindir specific paths
==============================================================================================

See :issue:`73516`

Description
===========

The PHP method `GeneralUtility::getFileAbsFileName` used for resolving absolute paths has the option removed to only
resolve relative paths or paths to the typo3/ main directory.


Impact
======

The two removed parameters are not evaluated anymore, thus always resolving any path, and additionally
always relative to the `PATH_site` variable (the installations' base directory).


Affected Installations
======================

Any installation with an extension using the removed options to fetch data relative to the typo3/ directory.


Migration
=========

Use the `EXT:` syntax everywhere to resolve files within extension directories. If the path relative to the
typo3/ main directory is explicitly needed, the constant `TYPO3_mainDir` can be used as a prefix to the file.

.. index:: PHP-API, TypoScript
