.. include:: ../../Includes.txt

======================================================================
Important: #84985 - Unified Workspace Restriction for Database Queries
======================================================================

See :issue:`84985`

Description
===========

A new `WorkspaceRestriction` is added to overcome certain downsides of the existing
`FrontendWorkspaceRestriction` and `BackendWorkspaceRestriction`. The new workspace restriction
limits a SQL query to only select records which are "online" (pid != -1) and in live or current
workspace.

As an important note and limitation of any workspace-related restrictions, fetching the exact
records need to be handled after the SQL results are fetched, by overlaying the records with
`BackendUtility::getRecordWSOL()`, `PageRepository->versionOL()` or `PlainDataResolver`.

For now, the WorkspaceRestriction must be used explicitly in various contexts and is not applied
automatically.

.. index:: Database, PHP-API, NotScanned
