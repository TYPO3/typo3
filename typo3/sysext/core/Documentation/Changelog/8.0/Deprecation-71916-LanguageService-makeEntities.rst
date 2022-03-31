
.. include:: /Includes.rst.txt

===================================================
Deprecation: #71916 - LanguageService->makeEntities
===================================================

See :issue:`71916`

Description
===========

The method `LanguageService->makeEntities()` was used when the TYPO3 Backend ran with non-utf8
characters in order to convert UTF-8 characters to latin1. This is not needed anymore as all is
UTF-8 now.


Impact
======

Using `$GLOBALS['LANG']->makeEntities()` will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using a third-party extension using the PHP method above.

.. index:: PHP-API
