..  include:: /Includes.rst.txt

..  _deprecation-110966-1791471928:

====================================================================
Deprecation: #110966 - PathUtility basename(), dirname(), pathinfo()
====================================================================

See :issue:`110966`

Description
===========

The following methods have been marked as deprecated and will be removed in
TYPO3 v16.0:

*  :php:`\TYPO3\CMS\Core\Utility\PathUtility::basename()`
*  :php:`\TYPO3\CMS\Core\Utility\PathUtility::dirname()`
*  :php:`\TYPO3\CMS\Core\Utility\PathUtility::pathinfo()`

They wrapped the native PHP functions to temporarily switch :php:`LC_CTYPE`
to the configured :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']`.
This option has been removed, see :ref:`breaking-110966-1791465024`, which
also explains why the native functions handle UTF-8 paths correctly in every
locale. The methods are now plain aliases of the native functions.

Impact
======

Calling one of the methods triggers a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
======================

Installations with extensions that call one of the methods are affected. The
extension scanner reports such usages as a strong match.

Migration
=========

Use the native PHP functions :php:`basename()`, :php:`dirname()` and
:php:`pathinfo()` instead. They take the same arguments and return the same
results:

..  code-block:: php

    // Before
    $fileName = PathUtility::basename($path);
    $directory = PathUtility::dirname($path);
    $extension = PathUtility::pathinfo($path, PATHINFO_EXTENSION);

    // After
    $fileName = basename($path);
    $directory = dirname($path);
    $extension = pathinfo($path, PATHINFO_EXTENSION);

..  index:: PHP-API, FullyScanned, ext:core
