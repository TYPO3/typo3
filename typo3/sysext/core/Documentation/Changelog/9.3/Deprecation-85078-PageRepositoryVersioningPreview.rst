.. include:: ../../Includes.txt

=======================================================
Deprecation: #85078 - PageRepository->versioningPreview
=======================================================

See :issue:`85078`

Description
===========

The public property :php:`$versioningPreview` in :php:`TYPO3\CMS\Frontend\Page\PageRepository` has been marked
as deprecated. The property was used in conjunction with :php:`$versioningWorkspaceId` which is set to a workspace
ID, in order to preview records of a workspace.

In order to ease the functionality for developers, only :php:`$versioningWorkspaceId` is taken into account now,
without needing to set :php:`$versioningPreview` anymore.


Impact
======

Setting or reading this option will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions using this property.


Migration
=========

Just set :php:`$versioningWorkspaceId` and remove any calls to the property.

.. index:: Frontend, PHP-API, FullyScanned