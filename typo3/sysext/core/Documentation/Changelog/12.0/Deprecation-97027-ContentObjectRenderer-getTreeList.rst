.. include:: /Includes.rst.txt

.. _deprecation-97027:

==========================================================
Deprecation: #97027 - ContentObjectRenderer->getTreeList()
==========================================================

See :issue:`97027`

Description
===========

The method :php:`ContentObjectRenderer->getTreeList()` has been marked as
deprecated.

The method signature has had various side-effects and too many options and
was used in different places across TYPO3 Core, where
:php:`ContentObjectRenderer` was not in use primarily.

Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

TYPO3 installations with third-party extensions accessing this method. This can
be checked via the Extension Scanner in the Install Tool.

Migration
=========

Several replacements for various use-cases have been introduced, which can be
found in :php:`PageRepository`. Instead of returning a comma-separated list of
integers as string, the methods now return an array of integer Page IDs, always
in the default language.

The method :php:`PageRepository->getPageIdsRecursive()` is used to retrieve all
subpages (recursively) of a list of pages, commonly used for fetching recursive
Storage PIDs in Plugins. Extbase is using this method under the hood.

The method :php:`PageRepository->getDescendantPageIdsRecursive()` is used to
return all subpages without the actual pages handed in as argument.

This might be useful for finding all subpages, to check for values or records
within such pages (e.g. Sitemap functionality).

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
