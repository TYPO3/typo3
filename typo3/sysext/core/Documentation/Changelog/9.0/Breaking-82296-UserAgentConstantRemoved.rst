.. include:: /Includes.rst.txt

====================================================
Breaking: #82296 - Removed constant TYPO3_user_agent
====================================================

See :issue:`82296`

Description
===========

The unused PHP constant :php:`TYPO3_user_agent` containing information of the User Agent string sent
for requests by TYPO3, has been removed.


Impact
======

Calling the constant will result in a PHP fatal error.


Affected Installations
======================

TYPO3 installations with third-party extensions that make use of the PHP constant.


Migration
=========

The extension scanner checks if the constant is used.

Any extension authors are encouraged to use php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['headers']['User-Agent']`
instead.

.. index:: PHP-API, FullyScanned
