..  include:: /Includes.rst.txt

..  _breaking-106503-1743667328:

============================================================
Breaking: #106503 - Removal of fields from sys_file_metadata
============================================================

See :issue:`106503`

Description
===========

The following database fields and corresponding TCA definitions have been
removed from the database table :sql:`sys_file_metadata` without substitution:

-   :sql:`visible`
-   :sql:`fe_groups`

These fields were added to the table when the system extension
EXT:filemetadata was installed.

Although the field names suggested access control functionality similar to
those found in other TYPO3 tables, they were never configured to restrict
frontend or backend access. Their intended meaning would have depended on
custom implementation and was not supported by the TYPO3 Core.

Additionally, the field :sql:`status` has been moved from the *Access* tab to
the *Metadata* tab to avoid the impression that this field has any restrictive
behavior.

Impact
======

The fields :sql:`visible` and :sql:`fe_groups` in :sql:`sys_file_metadata` are
no longer used. After performing a **Database Compare** in the Install Tool,
these columns will be removed and their data permanently lost.

Accessing these fields in PHP or TypoScript will result in PHP warnings.

Affected installations
======================

Any TYPO3 installation using the fields :sql:`visible` or :sql:`fe_groups`
provided by the system extension EXT:filemetadata is affected.

Migration
=========

No automatic migration is available.

If the fields are still required for custom logic, reintroduce their database
columns and TCA configuration within a custom extension.

..  index:: TCA, NotScanned, ext:filemetadata
