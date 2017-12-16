
.. include:: ../../Includes.txt

====================================================
Important: #68128 - PHP Magic Quote Handling removed
====================================================

See :issue:`68128`

Description
===========

In PHP versions prior to PHP 5.4 the option of adding slashes (magic quotes) to
the superglobals `$_GET` and `$_POST` was causing inconsistent data handling.
TYPO3 therefore always added slashes to these variables on every request to
streamline the handling of the superglobals. The corresponding methods
`GeneralUtility::_GET()`, `GeneralUtility::_GP()` and `GeneralUtility::_POST()`
have been changed to not strip off slashes anymore.

Since the PHP option was completely removed, TYPO3 is not adding slashes anymore,
and also does not strip the slashes anymore when using the methods within GeneralUtility.
