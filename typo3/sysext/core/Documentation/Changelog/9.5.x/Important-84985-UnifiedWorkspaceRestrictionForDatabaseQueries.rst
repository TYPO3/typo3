.. include:: /Includes.rst.txt

.. _important-84985:

======================================================================
Important: #84985 - Unified Workspace Restriction for Database Queries
======================================================================

See :issue:`84985`

Description
===========

A new :php-short:`\TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction` is added to overcome certain downsides of the existing
:php-short:`\TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction` and :php-short:`\TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction`. The new workspace restriction
limits a SQL query to only select records which are "online" (pid != -1) and in live or current
workspace.

As an important note and limitation of any workspace-related restrictions, fetching the exact
records need to be handled after the SQL results are fetched, by overlaying the records with
:php:`BackendUtility::getRecordWSOL()`, :php-short:`\TYPO3\CMS\Frontend\Page\PageRepository->versionOL()` or :php-short:`\TYPO3\CMS\Core\DataHandling\PlainDataResolver`.

For now, the :php-short:`\TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction` must be used explicitly in various contexts and is not applied
automatically.

.. index:: Database, PHP-API
