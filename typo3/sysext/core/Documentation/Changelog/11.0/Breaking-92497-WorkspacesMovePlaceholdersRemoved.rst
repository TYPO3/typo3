.. include:: /Includes.rst.txt

========================================================
Breaking: #92497 - Workspaces: Move Placeholders removed
========================================================

See :issue:`92497`

Description
===========

Workspaces had a so-called Move Placeholder since TYPO3 4.2, which
indicated that a versioned record (the move pointer) was moved to a
new location - either to a new page or to a different sorting position.

When querying records in a workspace, the Move Placeholder was included in the
initial database query, and then reverted to the actual live record
(to get the original PID), and then overloaded with the versioned record
(Move Pointer) containing other modified fields.

The main two fields of this Move Placeholder record were the PID
and the sorting, referring to the newly moved location. All
other fields were insignificant. An additional field "t3ver_move_id" contained
the actual live record ID.

Move Placeholders were identified by having the following field values:

* t3ver_state = 3 - indicating the type Move Placeholder
* t3ver_wsid = the workspace ID where a record was moved
* t3ver_oid = 0, in order to fetch them from the database together with live records
* pid = new Page location
* sorting (optional) = the new sorting location
* t3ver_move_id = the live version which was moved in the workspace

The Move Pointer is indicated like this:

* t3ver_state = 4 - indicating the type Move Pointer
* t3ver_wsid = the workspace ID where a record was moved
* t3ver_oid = the live version which was moved in the workspace
* pid = new Page location
* sorting (optional) = the new sorting location

Due to a significant change in TYPO3 v10, the Move Pointer (versioned record)
now also has the new PID, which was previously set to "-1", indicating a
versioned record. However, since all information is now also available in
the Move Pointer, the move placeholder database record is not needed anymore.

Move Placeholders are now neither evaluated, nor created by TYPO3 Core anymore,
and remaining move placeholders are removed with an Upgrade Wizard.

The TCA setting :php:`$TCA[$table][ctrl][shadowColumnsForMovePlaceholders]`
is not evaluated anymore and removed at TCA building-time.

The main benefits of this change:

* fewer database queries when fetching records within a workspace
* more consistent handling with versioned records
* less complexity within TYPO3's internal API
* fewer database records when working with TYPO3's Workspaces feature


Impact
======

When querying database records in a workspace, all Move Pointer records
are now fetched directly instead of the Move Placeholder records.
This is all done with the existing API methods in :php:`PageRepository`, :php:`BackendUtility`
and the Doctrine DBAL Workspace Restriction.

When moving a record in a workspace, Move Placeholders are not created anymore,
making them obsolete, as all information is now stored in the Move Pointer.

The constant :php:`VersionState::MOVE_PLACEHOLDER` is obsolete.

Lots of internal functionality regarding move placeholders has been removed.

The ctrl section :php:`$TCA[$table][ctrl][shadowColumnsForMovePlaceholders]` is automatically removed
from any table with a deprecation notice.

The database field :sql:`t3ver_move_id` is obsolete and not created
automatically for workspace enabled tables anymore.


Affected Installations
======================

Any TYPO3 installation using Workspaces which also hooks into the
workspaces-internal process via third-party extensions.

Any TYPO3 extension not using the Doctrine DBAL restrictions for handling
Workspaces.


Migration
=========

Run the upgrade wizard to remove any obsolete Move Placeholder records.

Use the TYPO3 API to read and write data from the database, including the
WorkspaceRestriction and the Versioning Overlay methods.

Remove the setting :php:`$TCA[$table][ctrl][shadowColumnsForMovePlaceholders]`
which is not evaluated anymore to avoid deprecation notices.

The database analyzer suggests the removal of database field :sql:`t3ver_move_id`
for various tables. The field can be safely dropped after the upgrade wizard
has been executed.

.. index:: Database, PHP-API, TCA, FullyScanned, ext:workspaces
