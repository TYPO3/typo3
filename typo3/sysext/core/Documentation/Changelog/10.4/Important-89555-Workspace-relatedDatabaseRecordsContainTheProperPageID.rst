.. include:: /Includes.rst.txt

==================================================================================
Important: #89555 - Workspace-related database records contain the proper Page ID.
==================================================================================

See :issue:`89555`

Description
===========

Back in 2006, when the workspaces functionality was added to TYPO3 v4.0, Kasper - the original author of TYPO3 - provided
an easy way to put workspaces on top while not worrying about existing logic. Every record that wasn't published had
the "pid" field set to "-1" - and thus was filtered out from any database query without having to worry about specific implementations.

14 years later, we have Doctrine DBAL and the solution for "enableFields" has widely been replaced by Database Restrictions,
allowing to modify database queries by TYPO3 Core without having to worry about custom queries.

For workspaces however, it is and was very tedious to find the "real pid" for versioned records,
and the "pid = -1" scenario is also one of the reasons why workspace overlays are more complex than they need to be.

For this reason, TYPO3 Core now handles versioned records by validating their "t3ver_wsid" (the workspace ID the record is versioned in),
"t3ver_state" (the type of the versioned record) and "t3ver_oid" (the live version of a record), and does not need to check for "pid=-1" anymore.

This opens up a more straightforward approach to select and overlay
records and reduce the need for some magic methods in TYPO3 Core,
which still exist.

An Upgrade Wizard transfers all "pid" fields of versioned records,
into the real "pid" fields. TYPO3 Core now only checks for versionized records based on the other fields above.

Please note: This only affects TYPO3 installations with workspaces enabled, and nothing should change for any extension if they use
proper WorkspaceRestriction or Workspace Overlay mechanisms in TYPO3 v10.

.. index:: Database, ext:workspaces
