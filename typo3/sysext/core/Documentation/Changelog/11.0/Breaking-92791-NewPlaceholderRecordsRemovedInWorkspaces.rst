.. include:: /Includes.rst.txt

==================================================================
Breaking: #92791 - "New Placeholder" records removed in Workspaces
==================================================================

See :issue:`92791`

Description
===========

When creating a new record in a workspace, TYPO3 created two database
entries: A "new placeholder" which served as a pseudo-live pendant with
no content but only the target PID value to be added, and a "versioned
record" which – until TYPO3 v10 – had the PID value "-1". On publishing
the contents of both records were exchanged (except the PID) and the
versioned record was removed.

Since TYPO3 v10 the "new placeholder" had little information to be kept
alive. Only on publishing, the behaviour was simple, as the publishing process
as described above worked the same way as for other "versioned records" like
modifying a live record, or moving records.

Apart from having two database records created where only one contained
user input, there were some conceptual drawbacks with having a placeholder record:
Sorting, and type fields had to be configured via a special
:php:`$TCA[$table][ctrl][shadowColumnsForNewPlaceholders]` TCA option, which wasn't kept in sync when
modifying the versioned record.

Both record types were identified in the database as the following:

New Placeholder Record
**********************

* t3ver_state => 1 - identifying as "new placeholder"
* t3ver_wsid => the ID of the workspace it was created
* t3ver_oid => 0 - as it should behave as the "online version"
* pid => the PID where the record should be published in (same with "sorting", when set)

New Versioned Record
********************

* t3ver_state => -1 - identifying as "new record created in workspace"
* t3ver_wsid => the ID of the workspace it was created
* t3ver_oid => ID of the New Placeholder Record
* pid => the PID where the record should be published in (same with "sorting", when set)

The placeholder record was queried when reading the database while in a workspace
with other live records. It was then overlaid by the versioned record.

TYPO3 v11 does not create placeholder records anymore, but instead creates
one record containing all information. When fetching records
from the database, the new versioned records are added directly, so no overlays
need to happen anymore, which speeds up performance when querying the
database via the TYPO3 Database via API classes such as :php:`PageRepository` or
:php:`BackendUtility`.

Impact
======

No records with :sql:`t3ver_state=-1` are found in the TYPO3 installation anymore.

When using the Doctrine DBAL API with Workspace Restrictions within a workspace,
the new versions are included in the SQL query result.

DataHandler will not create placeholders anymore, making the TCA option
:php:`$TCA[$table][ctrl][shadowColumnsForNewPlaceholders]` obsolete.

Using methods like :php:`getWorkspaceVersionOfRecord` on a new versioned record
will return the same record again, as there is no "workspace version" of this
record anymore.

The CLI command `cleanup:versions` is adapted as the option
`--action=unused_placeholders` is removed.

In addition, records overlaid via the TYPO3 API classes that have been
newly created in a workspace do not carry the :sql:`ORIG_uid` information anymore
which keeps the UID of the versioned record.

Affected Installations
======================

TYPO3 installations using Workspaces with newly created records that haven't
been published yet, or with third-party extensions directly querying, resolving
or writing based on :sql:`t3ver_state` database fields, which is very uncommon.

Migration
=========

An upgrade wizard is used to migrate possibly left-over "placeholder" records
within the the database. This is only needed when workspaces are in use and there
are records in the database that are newly created and have not been
published.

A TCA migration will automatically remove and log any usages of the TCA option
:php:`$TCA[$table][ctrl][shadowColumnsForNewPlaceholders]`.

It is highly recommend to use the TYPO3 API methods within Extbase, :php:`PageRepository`
and :php:`BackendUtility` to ensure records are resolved properly.

At any times, it is recommended to use the :php:`WorkspaceRestriction` of TYPO3's
implementation of Doctrine DBAL in conjunction with Workspace overlays.

.. index:: Database, FullyScanned, ext:workspaces
