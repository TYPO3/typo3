.. include:: ../../Includes.txt

=======================================
Deprecation: #85120 - JavaScriptEncoder
=======================================

See :issue:`85120`

Description
===========

The standalone utility class :php:`TYPO3\CMS\Core\Encoder\JavaScriptEncoder` has been superseded in TYPO3 6.2
by PHP's native :php:`json_encode()` and :php:`GeneralUtility::quoteJSvalue()` which provide significant
performance improvements. The utility class is thus marked for removal in TYPO3 v10.0.


Impact
======

Instantiating the class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions using this PHP class.


Migration
=========

Use :php:`GeneralUtility::quoteJSvalue()` or :php:`json_encode()` with proper options as second parameter to
escape a string for JavaScript output.

.. index:: PHP-API, FullyScanned