.. include:: /Includes.rst.txt

===============================================================================
Deprecation: #95293 - StringUtility::beginsWith() and StringUtility::endsWith()
===============================================================================

See :issue:`95293`

Description
===========

The helper methods :php:`StringUtility::beginsWith()` and
:php:`StringUtility::endsWith()` have been marked as deprecated, as the newly
available PHP-built in functions :php:`str_starts_with()` and
:php:`str_ends_with()` can be used instead, which support proper typing and
is faster on PHP 8.0.

For PHP 7.4 installations, the dependency `symfony/polyfill-php80` adds the
PHP functions in lower PHP environments, which TYPO3 Core ships as dependency
since TYPO3 v10 LTS.


Impact
======

Calling :php:`StringUtility::beginsWith()` or :php:`StringUtility::endsWith()`
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations using these TYPO3 API functions - either via extensions or
in their own site-specific code. An analysis via TYPO3's extension scanner
will show any matches.


Migration
=========

Replace all calls of :php:`StringUtility::beginsWith()` with
:php:`str_starts_with()` and :php:`StringUtility::endsWith()`
with :php:`str_ends_with()` to avoid deprecation warnings and to keep your
code up-to-date.

See `php.net: str-starts-with <https://www.php.net/manual/en/function.str-starts-with.php>`_
and `php.net: str-ends-with <https://www.php.net/manual/en/function.str-ends-with.php>`_
for further syntax.

.. index:: PHP-API, FullyScanned, ext:core
