.. include:: ../../Includes.txt

================================================================
Important: #90911 - Package algo26-matthias/idna-convert removed
================================================================

See :issue:`90911`

Description
===========

The TYPO3 core dependency / composer library `algo26-matthias/idna-convert` does not support PHP 7.4
in its currently used version. It has been removed from the composer dependencies and the current used
code is placed into `typo3/sysext/core/Resources/PHP/idna-converter` to support directly usage of
that package.

This makes it possible to use TYPO3 v9 with umlaut domain validation (e.g. also when using EXT:form
with sending an email to someone with a umlaut domain as recipient) in conjunction with TYPO3 v9 and
PHP 7.4.

If the PHP code of the package is used directly by third-party extensions, this will have no further
side effects, as the TYPO3 core still provides the source code, but be aware will not work with
PHP 7.4.

If you like to use the package in a newer version, follow the docs https://idnaconv.net/docs.html
and add the custom PHP code yourself.

If you are using the TYPO3 API `GeneralUtility::idnaEncode()` everything works as before, but now
also with PHP 7.4 support.

.. index:: PHP-API, ext:core
