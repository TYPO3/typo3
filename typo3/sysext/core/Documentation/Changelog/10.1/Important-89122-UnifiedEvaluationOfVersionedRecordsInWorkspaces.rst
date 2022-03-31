.. include:: /Includes.rst.txt

=========================================================================
Important: #89122 - Unified evaluation of versioned records in workspaces
=========================================================================

See :issue:`89122`

Description
===========

TYPO3 Core handled the result of database queries in a lot of different ways to filter out workspace records.
In previous versions, where versioned records without workspaces (incremental versions) was supported, the main
identifier was always to check for records that are "Offline" - by checking via the "pid" field of database records,
that they are set to "-1".

With workspaces, there are other, better ways to identify versioned via the following fields:

- t3ver_state (what kind of versioned record it is - new version, moved record, deleted version)
- t3ver_oid (if the versioned record points to a live record)
- t3ver_wsid (the workspace ID, a relation to a sys_workspace record)

The "pid" field was kept as misuse, but fine for most of the database queries. With the unified database abstraction
layer based on Doctrine DBAL and enriched via Query Restrictions, TYPO3 Core now checks for t3ver_state, t3ver_wsid
and t3ver_oid to identify versioned records.

This is already achieved with any database query by using the WorkspaceRestrictions in place. Extension authors
should use Doctrine DBAL and apply workspace restrictions by default.

If this is not possible when building custom queries without restrictions, it is recommended to check for:

- t3ver_oid>0 = identifying a versioned record that has a counterpart in the live workspace
- t3ver_wsid=13 - identifying a versioned record or placeholder that resides in a specific workspace (in this case "13")
- t3ver_state IN (0,-1) AND t3ver_wsid IN (0,13) - to fetch records including "new record" placeholders

Checking for "pid = -1" is not recommended anymore - using the restrictions and custom query information can be
used in previous TYPO3 versions already.


.. index:: Database, ext:workspaces
