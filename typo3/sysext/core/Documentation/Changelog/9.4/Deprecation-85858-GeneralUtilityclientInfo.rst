.. include:: /Includes.rst.txt

==================================================
Deprecation: #85858 - GeneralUtility::clientInfo()
==================================================

See :issue:`85858`

Description
===========

The helper method :php:`GeneralUtility::clientInfo()` responsible for
parsing the server variable :php:`$_SERVER['HTTP_USER_AGENT']` has been marked
as deprecated.

This method is not up-to-date with current browser headers, and in light of
browser that are able to fake the HTTP_USER_AGENT the detection is not practical
anymore.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling this method.


Migration
=========

Depending on the use-case, it is best to use the PSR-7-based request object,
if available in the context, or `$_SERVER['HTTP_USER_AGENT']` to detect a
specific browser/client user agent.

.. index:: PHP-API, FullyScanned, ext:core
