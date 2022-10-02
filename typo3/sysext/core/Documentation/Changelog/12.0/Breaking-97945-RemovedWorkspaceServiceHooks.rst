.. include:: /Includes.rst.txt

.. _breaking-97945:

=================================================
Breaking: #97945 - Removed WorkspaceService hooks
=================================================

See :issue:`97945`

Description
===========

The hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Workspaces\Service\WorkspaceService']['hasPageRecordVersions']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Workspaces\Service\WorkspaceService']['fetchPagesWithVersionsInTable']`,
used to manipulate the state of versions for pages and tables have been removed.

This information has been used to highlight pages in the page tree. This
modification however can now be done using the new PSR-14
:php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent`.

Impact
======

Any hook implementation registered is not executed anymore since
TYPO3 v12.0. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using these hooks in custom extension code.

Migration
=========

The hooks are removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-97945-PSR14AfterPageTreeItemsPreparedEvent>`
as replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:workspaces
