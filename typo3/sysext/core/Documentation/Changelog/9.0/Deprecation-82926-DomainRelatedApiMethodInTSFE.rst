.. include:: ../../Includes.txt

=======================================================
Deprecation: #82926 - Domain-related API method in TSFE
=======================================================

See :issue:`82926`

Description
===========

The method :php:`TypoScriptFrontendController->getDomainNameForPid()` has been marked as deprecated.


Impact
======

Calling the method will trigger a deprecation warning.


Affected Installations
======================

Any third-party extension using this method to retrieve a domain name for a given Page ID.


Migration
=========

Use the method ``TypoScriptFrontendController->getDomainDataForPid()`` which returns more
data from a domain record as array.

.. index:: PHP-API, FullyScanned, Frontend