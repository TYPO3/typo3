.. include:: ../../Includes.txt

===================================================
Breaking: #79243 - Remove l10n_mode mergeIfNotBlank
===================================================

See :issue:`79243`

Description
===========

The setting `mergeIfNotBlank` has been removed without replacement from the list of possible values of
the TCA column property `l10n_mode`.


Impact
======

Previously values of a localization having a dependent parent record were taken
from the parent record if `l10n_mode` for the particular field was set to
`mergeIfNotBlank` and the value in the localization was empty. Now, this value
is duplicated during the creation of the localized record and has to be
modified manually if required.


Affected Installations
======================

All instances with extensions setting TCA options and having
`$GLOBALS['TCA'][<table-name>]['columns'][<column-name>]['l10n_mode']` set to `mergeIfNotBlank`.


Migration
=========

First execute the upgrade wizard
**Migrate values in database records having "l10n_mode" set** in the install tool.
After that, remove `$GLOBALS['TCA'][<table-name>]['columns'][<column-name>]['l10n_mode']`
if it is set to `mergeIfNotBlank`. If `l10n_mode` is removed before the upgrade wizard
has been executed, nothing will be migrated - thus, it's important to keep that order
of migration.

The upgrade wizard executes the following field usages:

* inline children, pointing to `sys_file_reference`:
  file references are localized for the the localization, if missing there
* group fields, basically not using MM intermediate tables:
  value is cloned to the accordant field in the localization, if empty there
* any other field type:
  value is cloned to the accordant field in the localization, is blank there

The term `blank` refers to an empty string (`''`), `empty` refers to an empty
string, null values and zero values (numeric and string).

.. index:: Database, TCA