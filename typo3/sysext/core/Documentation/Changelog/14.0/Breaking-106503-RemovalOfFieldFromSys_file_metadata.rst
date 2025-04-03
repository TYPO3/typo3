..  include:: /Includes.rst.txt

..  _breaking-106503-1743667328:

===========================================================
Breaking: #106503 - Removal of field from sys_file_metadata
===========================================================

See :issue:`106503`

Description
===========

The following database fields and TCA definitions have been removed from the DB table `sys_file_metadata` without substitution:

- `visible`
- `fe_groups`

These fields are added to the DB table when the system extension `filemetadata` is installed.

Both fields seemed like being fields known from any other table which restrict access to the content but they have never been
configured so, which is also depending on the use-case and not possible from TYPO3 Core usages.

Additionally, the field `status` has been moved from the tab *Access* to *Metadata* to avoid the notion that this field has
any restrictive behaviour.


Impact
======

The fields `visible` and `fe_groups` of the database table `sys_file_metadata` are not used anymore. After a Database Compare
in the Install Tool, the columns are removed and its content lost. When accessing the DB fields via PHP or TypoScript,
a PHP warning might exist.


Affected installations
======================

Any TYPO3 installation using the mentioned fields by having EXT:filemetadata installed.


Migration
=========

No migration is available.

If the fields are in use, it is recommended to re-add the TCA definitions in a custom extension.


..  index:: TCA, NotScanned, ext:filemetadata
