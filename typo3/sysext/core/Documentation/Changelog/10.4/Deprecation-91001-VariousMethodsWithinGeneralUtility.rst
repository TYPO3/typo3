.. include:: /Includes.rst.txt

===========================================================
Deprecation: #91001 - Various methods within GeneralUtility
===========================================================

See :issue:`91001`

Description
===========

The following methods within GeneralUtility have been marked as deprecated,
as the native PHP methods can be used directly:

* :php:`GeneralUtility::IPv6Hex2Bin()`
* :php:`GeneralUtility::IPv6Bin2Hex()`
* :php:`GeneralUtility::compressIPv6()`
* :php:`GeneralUtility::milliseconds()`

In addition, these methods are unused by Core and marked as deprecated as well:

* :php:`GeneralUtility::linkThisUrl()`
* :php:`GeneralUtility::flushDirectory()`


Impact
======

Calling any methods directly from PHP will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with third-party extensions using any of these methods.


Migration
=========

As the following methods are just wrappers around native PHP methods, it is
recommended to switch to native PHP to speed up performance:

* :php:`GeneralUtility::IPv6Hex2Bin($hex)`: :php:`inet_pton($hex)`
* :php:`GeneralUtility::IPv6Bin2Hex($bin)`: :php:`inet_ntop($bin)`
* :php:`GeneralUtility::compressIPv6($address)`: :php:`inet_ntop(inet_pton($address))`
* :php:`GeneralUtility::milliseconds()`: :php:`round(microtime(true) * 1000)`

As for :php:`GeneralUtility::linkThisUrl()` it is recommended to migrate to
PSR-7 (UriInterface).

The method :php:`GeneralUtility::flushDirectory()` uses a clearing
folder structure which is only used for caching to avoid race-conditioning. It
is recommended to use :php:`GeneralUtility::rmdir()` or implement the code
directly in the third-party extension.

.. index:: PHP-API, FullyScanned, ext:core
