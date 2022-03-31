.. include:: /Includes.rst.txt

======================================================================
Important: #84985 - Unified Workspace Restriction for Database Queries
======================================================================

See :issue:`84985`

Description
===========

A new :php:`WorkspaceRestriction` is added to overcome certain downsides of the existing
:php:`FrontendWorkspaceRestriction` and :php:`BackendWorkspaceRestriction`. The new workspace restriction
limits a SQL query to only select records which are "online" (pid != -1) and in live or current
workspace.

As an important note and limitation of any workspace-related restrictions, fetching the exact
records need to be handled after the SQL results are fetched, by overlaying the records with
:php:`BackendUtility::getRecordWSOL()`, :php:`PageRepository->versionOL()` or :php:`PlainDataResolver`.

For now, the :php:`WorkspaceRestriction` must be used explicitly in various contexts and is not applied
automatically.

.. index:: Database, PHP-API
