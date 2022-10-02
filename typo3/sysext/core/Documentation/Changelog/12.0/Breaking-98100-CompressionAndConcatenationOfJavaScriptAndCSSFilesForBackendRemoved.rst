.. include:: /Includes.rst.txt

.. _breaking-98100-1659877890:

================================================================================================
Breaking: #98100 - Compression and Concatenation of JavaScript and CSS files for Backend removed
================================================================================================

See :issue:`98100`

Description
===========

Extension `backend` introduced compression and concatenation of CSS and JavaScript
files in version 4.3 due to limitations of Internet Explorer 9 and lower.
Since then, extension `backend` uses JavaScript modules and loading via RequireJS and
ES Modules, as well as CSS compression and concatenation by default during
build time.

For this reason, this feature is removed from the actual `ResourceCompressor`,
which only works in TYPO3 Frontend rendering now via the common TypoScript
settings.

Impact
======

A custom handler for concatenation and compression of JavaScript and CSS files has
no effect anymore when registered in a third-party extension.

This could previously be configured via

* :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['jsConcatenateHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['jsCompressHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['cssConcatenateHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['cssCompressHandler']`

Additionally, it was previously possible to configure a custom root path
in ResourceCompressor via :php:`setRootPath($rootPath)`, which has been removed
as well.

Affected installations
======================

TYPO3 installations with custom JavaScript and CSS handlers for TYPO3 Backend routines
via custom extensions which is highly unlikely.

Migration
=========

None, as component-based CSS files and module-based JavaScript files are loaded already
anyway, and the performance impact of loading multiple files is rather low due
to optimized :file:`.htaccess` configurations already, and through bundling all CSS for
Core in optimized files as well.

.. index:: LocalConfiguration, PHP-API, FullyScanned, ext:backend
