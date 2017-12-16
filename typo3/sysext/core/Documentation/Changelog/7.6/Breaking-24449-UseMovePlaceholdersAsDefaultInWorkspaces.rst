
.. include:: ../../Includes.txt

=================================================================
Breaking: #24449 - Use move placeholders as default in workspaces
=================================================================

See :issue:`24449`

Description
===========

The ctrl section of each TCA table has the property "versioningWS" which might be set to "1" (enabled) or to "2"
which enables "move placeholders" functionality.

The "move placeholders" are now active by default, removing the possibility to have a "simple workspace" concept
which does not consider sorting records inside a workspace.


Impact
======

All checks in TYPO3 consider all TCA tables that have workspaces enabled ("versioningWS") to be
move-placeholder-aware. All TCA tables that only have non-moveable-records in workspace now need the DB
table field "t3ver_moveid" to be added.

All existing TCA configurations with "versioningWS" can now simply be set to TRUE instead of "2".


Affected Installations
======================

Any installation with third-party extensions that use workspace functionality but do not have move-placeholder-enabled records.


Migration
=========

Make all TCA tables "move-placeholders" aware by adding the necessary database field "t3ver_moveid".
