.. include:: ../../Includes.txt

============================================================================
Feature: #86001 - Regular Workspace cleanup tasks available via CLI commands
============================================================================

See :issue:`86001`

Description
===========

TYPO3 now supports two new symfony-based commands to trigger regular tasks, which were previously only
available when the scheduler component was available.

* typo3/sysext/core/bin/typo3 workspace:autopublish

Checks for workspaces with auto-publishing configured and does a publishing/swapping process.

* typo3/sysext/core/bin/typo3 cleanup:previewlinks

Removes expired previewlinks stored within `sys_preview` from the database.


Impact
======

It is possible to execute these commands from the CLI context without having to install the scheduler extension
and to create a scheduler task for that.

.. index:: CLI, ext:workspaces