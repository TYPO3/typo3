.. include:: /Includes.rst.txt

========================================================================================================
Breaking: #88640 - Database field "sys_template.nextLevel" and TypoScript sublevel - inheritance removed
========================================================================================================

See :issue:`88640`

Description
===========

The database field :sql:`nextLevel` of the database table :sql:`sys_template` where TypoScript configuration
is stored, has been removed.

The field :sql:`nextLevel` was introduced in TYPO3 v3.x before TypoScript could be imported from
external files.

Nowadays, TypoScript conditions should be used much more instead of this :sql:`nextLevel` feature,
which is kind of a pseudo-condition.


Impact
======

The database field is removed, and not evaluated anymore in TypoScript compilation.

Requesting the database field in custom database queries will result in an SQL error.


Affected Installations
======================

TYPO3 installations that have :sql:`sys_template` records with this flag activated,
or querying this database field in third-party extensions.


Migration
=========

Check for existing :sql:`sys_template` records having this flag activated by executing
this SQL command:

:sql:`SELECT * FROM sys_template WHERE nextLevel>0 AND deleted=0;`

before updating TYPO3 Core.

Replace the sys_template record (the uid of the record is stored in the "nextLevel" field) with a condition e.g. :typoscript:`[tree.level > 1]` to add TypoScript for subpages.

.. index:: Database, NotScanned
