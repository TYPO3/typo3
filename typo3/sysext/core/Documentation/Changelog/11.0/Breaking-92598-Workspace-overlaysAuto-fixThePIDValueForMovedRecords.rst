.. include:: /Includes.rst.txt

==============================================================================
Breaking: #92598 - Workspace-overlays auto-fix the PID value for moved records
==============================================================================

See :issue:`92598`

Description
===========

When handling versioned records while reading data from the database,
the common behavior is to apply a "workspace overlay". When using the TYPO3 API
in both classes like:

* :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository->getRecord()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL()`

the currently live records are fetched from the database and being overlayed with
possible versioned record in a specific workspace by replacing all selected fields
with the versioned values.

However, the fields "uid" and "pid" of the live record were kept, and the values
of the versioned record were stored in "_ORIG_uid" and "_ORIG_pid"
when a record was successfully overlaid.

This was necessary in the past, because the versioned records did not contain
a meaningful "pid" value ("pid=-1") so in order to keep a useful value,
the live value was kept.

The meaning of "_ORIG_pid" has now changed:

* All versioned records contain the same "pid" as the live record, so the
  "_ORIG_pid" value is not needed anymore.
* However, when a record is moved to another page in a workspace, the PID changes.
  Handling this case is drastically simpler now. In order to work with the
  modified data in moved versions, the "pid" field now contains the value of
  the new page in a workspace, and the "_ORIG_pid" field contains
  the value of the live record's "pid" field.
  This behavior is now streamlined with what :php:`fixVersioningPid()` was doing.
  Therefore :php:`fixVersioningPid()` has been marked as deprecated.

Impact
======

When using workspaces and the API methods, the "_ORIG_pid" field is only set
for moved records where a workspace overlay has been properly applied.

The "pid" field now always contains the actual pid of a versioned record in
the workspace, where as the "_ORIG_pid" contains the live record pid value.

In other words: if a moved record has been overlaid, the "_ORIG_pid" and "pid" field values
are now switched.

Affected Installations
======================

TYPO3 installations with custom code regarding workspaces that dealt with
the value "_ORIG_pid" for resolving moved records in a workspace.

Migration
=========

The change helps to reduce the complexity when dealing with workspace overlays,
so existing PHP code probably does not need to check for "_ORIG_pid" anymore,
and extension developers can just safely use the overlay methods and directly
use the "pid" field, knowing that the "pid" field contains the value of the
record within the workspace.

.. index:: PHP-API, NotScanned, ext:workspaces
