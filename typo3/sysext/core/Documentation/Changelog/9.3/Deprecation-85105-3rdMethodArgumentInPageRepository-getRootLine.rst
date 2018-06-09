.. include:: ../../Includes.txt

==========================================================================
Deprecation: #85105 - 3rd method argument in PageRepository->getRootLine()
==========================================================================

See :issue:`85105`

Description
===========

The third argument of :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRootLine()` has been marked
as deprecated.

That argument was mainly used to catch exceptions when a faulty rootline is found. The PageRepository
currently handles the exceptions in a special way with some special magic. However, it is more
feasible to always throw an exception and have the caller handle possible exceptions.


Impact
======

Calling the method with three arguments will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions calling the method directly with the 3rd argument.


Migration
=========

Remove the third argument from code in PHP and wrap a try/catch block around the method call.

.. index:: Frontend, PHP-API, FullyScanned