.. include:: ../../Includes.txt

============================================================================
Breaking: #82709 - TCA option "localizeChildrenAtParentLocalization" removed
============================================================================

See :issue:`82709`

Description
===========

The TCA option :php:`$TCA[$tableName]['columns'][$columnName]['config']['behaviour']['localizeChildrenAtParentLocalization']`
has been removed, as this is the default behaviour for any kind of inline translation (IRRE).

The behaviour to disable this functionality in TYPO3 v8 was not compatible anymore with any
localization mode setting and the newly introduced `allowLanguageSynchronization`.


Impact
======

Explicitly disabling this option has no effect anymore, setting this option in TCA will
trigger a deprecation message.


Affected Installations
======================

Any installation with custom TCA definitions of Inline Relational Record Editing which have this
setting set.


Migration
=========

Remove the TCA option in the extensions' TCA definition.

.. index:: TCA, Backend, NotScanned