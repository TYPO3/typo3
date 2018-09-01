.. include:: ../../Includes.txt

=================================================
Deprecation: #85557 - PageRepository->getRootLine
=================================================

See :issue:`85557`

Description
===========

The public method :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRootLine()` has been marked as
deprecated.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions calling this method directly.


Migration
=========

As `getRootLine()` acts as a simple wrapper around `RootlineUtility`, it is recommended to instantiate
the RootLineUtility directly and catch any specific exceptions directly.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
