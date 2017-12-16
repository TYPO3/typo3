
.. include:: ../../Includes.txt

====================================================================
Breaking: #67825 - Remove colorpicker options "dim" and "tableStyle"
====================================================================

See :issue:`67825`

Description
===========

`TCA` colorpicker options "dim" and "tableStyle" have been removed.


Impact
======

The TCA options won't have any effect anymore.


Affected Installations
======================

Any extension that has a colorpicker wizard configured in `TCA` and uses `dim`
or `tableStyle` options is effected.


Migration
=========

Both options can de safely removed.
