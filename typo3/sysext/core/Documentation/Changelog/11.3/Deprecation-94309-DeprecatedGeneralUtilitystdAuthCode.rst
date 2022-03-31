.. include:: /Includes.rst.txt

=================================================
Deprecation: #94309 - GeneralUtility::stdAuthCode
=================================================

See :issue:`94309`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::stdAuthCode()`
has not been used within the Core since at least v9. It internally fiddles
with the `encryptionKey` while using :php:`md5()`. Furthermore, the default
length of 8 chars could easily lead to hash collisions. The TYPO3 Core already
provides :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()` for such
purposes, which is using `sha1` with a length of 40. Therefore,
:php:`stdAuthCode()` has been deprecated and will be removed in TYPO3 v12.

Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All TYPO3 installations calling this method in custom code. The extension
scanner will find all usages as strong match.

Migration
=========

Replace all usages of the method in custom extension code by either using
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hmac()` or by a custom
implementation.

.. index:: PHP-API, FullyScanned, ext:core
