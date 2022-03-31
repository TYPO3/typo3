.. include:: /Includes.rst.txt

==========================================================
Deprecation: #85554 - PageRepository->checkWorkspaceAccess
==========================================================

See :issue:`85554`

Description
===========

The unused method :php:`TYPO3\CMS\Frontend\Page\PageRepository->checkWorkspaceAccess()` has been marked as
deprecated.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions calling this public method directly.


Migration
=========

Implement the check on :php:`BE_USER->checkWorkspace($workspaceId)` directly in the callers code.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
