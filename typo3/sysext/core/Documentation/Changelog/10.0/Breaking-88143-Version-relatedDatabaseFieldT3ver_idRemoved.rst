.. include:: /Includes.rst.txt

====================================================================
Breaking: #88143 - Version-related database field "t3ver_id" removed
====================================================================

See :issue:`88143`

Description
===========

The database field for all workspace-enabled database tables :sql:`t3ver_id` is removed. It previously
contained an incrementing numeric value when using incrementing versioning - the versioning concept
which was in place before Workspaces were introduced in TYPO3 v4.0.

Since the legacy versioning was removed in TYPO3 v9, the field is removed and not automatically
created for new installations anymore.


Impact
======

Creating SQL statements in custom extensions explicitly selecting this field will result in SQL
errors.

In addition, when upgrading TYPO3 to v10.0 this field will be removed by the Database Analyzer
Tool in the Install Tool for all TYPO3 core database tables and extensions using the automatic
creation of database fields.


Affected Installations
======================

All installations with custom extensions explicitly requesting this field.


Migration
=========

Search in any extension in `typo3conf/ext` for :sql:`t3ver_id` to see any usages, and remove the field
from any queries, database definitions in :file:`ext_tables.sql` files.

.. index:: Database, NotScanned, ext:workspaces
