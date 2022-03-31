.. include:: /Includes.rst.txt

=============================================================
Deprecation: #90800 - GeneralUtility::isRunningOnCgiServerApi
=============================================================

See :issue:`90800`

Description
===========

The lowlevel API method :php:`GeneralUtility::isRunningOnCgiServerApi()` which detects if
the current PHP is executed via a CGI wrapper script ("SAPI", see https://www.php.net/manual/en/function.php-sapi-name.php) has been
moved to the Environment API and is now available via :php:`Environment::isRunningOnCgiServer()`.


Impact
======

Calling the method from :php:`GeneralUtility` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with an extension using this PHP method, which will happen only in rare circumstances.


Migration
=========

Use the new method :php:`Environment::isRunningOnCgiServer()` instead, which works exactly the same.

.. index:: PHP-API, FullyScanned, ext:core
