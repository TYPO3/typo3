====================================================================
Breaking: #77591 - Removed WorkspaceService->isOldStyleWorkspaceUsed
====================================================================

Description
===========

The method ``WorkspaceService->isOldStyleWorkspaceUsed()`` was removed without substitution. It existed to identify if
Workspaces still were configured for TYPO3 4.4.


Impact
======

Calling the PHP method directly will result in a fatal PHP error.


Affected Installations
======================

TYPO3 installations using workspaces and extending the workspaces functionality extensively by providing a fallback layer to functionality for TYPO3 4.4 or lower.


Migration
=========

Remove any occurrences to the PHP method.