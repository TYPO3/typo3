.. include:: /Includes.rst.txt

========================================================================
Breaking: #81950 - Remove leftover workspaces unpublishing functionality
========================================================================

See :issue:`81950`

Description
===========

A property within workspaces for "unpublishing" published records has been disabled since TYPO3 4.5.

This functionality allowed to restore a published workspace which was published at a given time, to revert the changes on another
time, but had side-effects if changes were made between publishing and unpublishing.

However, this functionality was not visible to TYPO3 out of the box, but only available with a possible third-party integration
since TYPO3 4.5. The feature was therefore removed from TYPO3 Core.

The (hidden) database field :sql:`sys_workspace.unpublish_time` was removed.


Impact
======

Using the functionality will not work anymore, operating on the database with this field will result in a SQL error.


Affected Installations
======================

Any installation using the workspace functionality with automatic publishing and a third-party extension for unpublishing.


Migration
=========

If this feature is required for an installation, the field should be re-added by the third-party extension in TCA (which was missing)
and the database which was using the functionality. On top, a custom auto-unpublishing CLI command should be created.

.. index:: Database, NotScanned, ext:workspaces
