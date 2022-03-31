.. include:: /Includes.rst.txt

===========================================================
Deprecation: #85556 - PageRepository->versioningWorkspaceId
===========================================================

See :issue:`85556`

Description
===========

The public property :php:`TYPO3\CMS\Frontend\Page\PageRepository->versioningWorkspaceId` has been marked as
deprecated.


Impact
======

Accessing or setting the property directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions calling this public property directly.


Migration
=========

Use the Context API and its workspace aspect

:php:`GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id', 0);`

directly when reading the workspace ID, or instantiate a custom PageRepository with a custom context (see Context
API docs) for custom usages.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
