.. include:: /Includes.rst.txt

=====================================================
Deprecation: #85971 - PageRepository->getFirstWebPage
=====================================================

See :issue:`85971`

Description
===========

The method php:`PageRepository->getFirstWebPage()` is only used when no "?id" parameter is given, and no rootpage was resolved.

As this is the only use-case, a more generic "getMenu" method can be used, which does the
same except for not "limiting" the query to one result, so there is a minimal memory penalty when doing so.
However due to Pseudo-Site functionality this drawback only applies to rare cases.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling this method.


Migration
=========

Use php:`PageRepository->getMenu()` instead.

.. index:: Frontend, FullyScanned, ext:frontend
