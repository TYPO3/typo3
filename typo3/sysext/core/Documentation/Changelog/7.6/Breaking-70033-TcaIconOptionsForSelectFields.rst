
.. include:: ../../Includes.txt

=====================================================
Breaking: #70033 - TCA icon options have been removed
=====================================================

See :issue:`70033`

Description
===========

The `TCA` configurations `noIconsBelowSelect`, `foreign_table_loadIcons` and `suppress_icons` for select fields with
the render type `selectSingle` have been removed.


Impact
======

The old TCA settings `noIconsBelowSelect`, `foreign_table_loadIcons` and `suppress_icons` are ignored and
deprecation log entries will be triggered. A migration handles the update of the settings.


Affected Installations
======================

All installations with extensions that configure the icon table visibility of TCA select fields with one of the old settings.


Migration
=========

Extension authors need to use the new option `showIconTable` to define the visibility of the icon table for their select fields.
