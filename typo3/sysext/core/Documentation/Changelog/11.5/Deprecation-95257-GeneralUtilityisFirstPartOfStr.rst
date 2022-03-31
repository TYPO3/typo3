.. include:: /Includes.rst.txt

========================================================
Deprecation: #95257 - GeneralUtility::isFirstPartOfStr()
========================================================

See :issue:`95257`

Description
===========

The helper method
:php:`TYPO3\CMS\Core\Utility\GeneralUtility\GeneralUtility::isFirstPartOfStr()`
has been marked as deprecated, as the newly available PHP built-in
function :php:`str_starts_with()` can be used instead, which
supports proper typing and is faster on PHP 8.0.

For PHP 7.4 installations, the dependency `symfony/polyfill-php80`
adds the PHP function in lower PHP environments, which the TYPO3
Core ships as dependency.


Impact
======

Calling :php:`GeneralUtility::isFirstPartOfStr()` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations using this TYPO3 API function - either via
extensions or in their own site-specific code. An analysis
via TYPO3's extension scanner will show any matches.


Migration
=========

Replace all calls of :php:`GeneralUtility::isFirstPartOfStr()` with
:php:`str_starts_with()` to avoid deprecation warnings and to keep
your code up-to-date.

See `php.net: str-starts-with <https://www.php.net/manual/en/function.str-starts-with.php>`_ for further syntax.

.. index:: PHP-API, FullyScanned, ext:core
