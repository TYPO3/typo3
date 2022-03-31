.. include:: /Includes.rst.txt

====================================================================================
Deprecation: #88746 - PageRepository PHP class moved from Frontend to Core Extension
====================================================================================

See :issue:`88746`

Description
===========

In previous TYPO3 versions, accessing records was mixed between Frontend (handled by PageRepository)
and Backend (handled by static methods in BackendUtility). In TYPO3 v9, the Context API was introduced
and PageRepository now acts as a strong database accessor which is not bound to Frontend anymore,
at all.

In addition, various places of the backend also used PageRepository already, which violated the
separation of packages, as TYPO3 Core aims to strictly separate Frontend and Backend application
code.

In the case of PageRepository, the code is used by both applications, and is therefore moved
to Core system extension (EXT:core), and renamed to :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository`.

Until TYPO3 v9, it was placed in :php:`TYPO3\CMS\Frontend\Page\PageRepository`.

In addition, all interface'd hooks are moved to EXT:core as well with the same PHP namespace.


Impact
======

A class alias was introduced which does not trigger any deprecations, so both variants
still work as before, however it is recommended to rename any calls to the PHP class.

No other functionality was changed.


Affected Installations
======================

Any TYPO3 installation with custom PHP extensions accessing PageRepository directly.


Migration
=========

Replace any PHP references of :php:`TYPO3\CMS\Frontend\Page\PageRepository`
to :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository` in any custom PHP code.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
