.. include:: /Includes.rst.txt

========================================================================
Breaking: #88744 - Database fields related to CSS Styled Content removed
========================================================================

See :issue:`88744`

Description
===========

CSS Styled Content was superseded with Fluid Styled Content in TYPO3 v7, and support was dropped
with TYPO3 v9. TYPO3 Core still shipped with some database fields that were kept to easy
manual migration for specific values in these fields.

These database fields within the database table :sql:`tt_content` have been removed.

* :sql:`tt_content.spaceBefore` (now used via space_before_class)
* :sql:`tt_content.spaceAfter` (now used via space_after_class)


Impact
======

Accessing the database fields with a custom SQL query will result in SQL errors or empty values.


Affected Installations
======================

TYPO3 installations from earlier TYPO3 versions (prior to v8) that still have CSS Styled Content
in use or adopted to migrate the fields to still render via CSS Styled Content.

Additionally, TYPO3 installations that mis-used the database fields for other purposes but
still rely on the presence of the database fields.


Migration
=========

If the database fields still contain value that hasn't been migrated, it is possible to re-add
these database fields in a custom extension.

It is recommended to switch to Fluid Styled Content rendering or custom content types with
custom additional fields.

.. index:: Database, Frontend, NotScanned, ext:frontend
