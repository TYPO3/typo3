.. include:: /Includes.rst.txt

================================================
Deprecation: #87894 - GeneralUtility::idnaEncode
================================================

See :issue:`87894`

Description
===========

PHP has the native function :php:`idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)` for converting UTF-8 based domains to ascii-based ("punicode")
which is available in all supported PHP versions using :php:`"symfony/polyfill-intl-idn"`.

For this reason the method :php:`GeneralUtility::idnaEncode()` has been marked as deprecated.


Impact
======

Calling :php:`GeneralUtility::idnaEncode()` directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with third-party extensions calling this method.


Migration
=========

Use :php:`idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);` instead.

Please be aware that contrary to :php:`GeneralUtility::idnaEncode()` the native PHP function only works on domain names, not email addresses or
similar. In order to encode email addresses split the address at the last :php:`'@'` and use :php:`idn_to_ascii()` on that last part.
Also, if there is an error in converting a string, a bool :php:`false` is returned.

.. index:: PHP-API, FullyScanned, ext:core
