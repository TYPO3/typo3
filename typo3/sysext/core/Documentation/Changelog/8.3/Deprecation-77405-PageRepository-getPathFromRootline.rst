
.. include:: ../../Includes.txt

=========================================================
Deprecation: #77405 - PageRepository->getPathFromRootline
=========================================================

See :issue:`77405`

Description
===========

The PHP method `PageRepository->getPathFromRootline()` has been marked as deprecated.


Impact
======

Calling the method will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with a third-party extension using this method.

.. index:: PHP-API, Frontend