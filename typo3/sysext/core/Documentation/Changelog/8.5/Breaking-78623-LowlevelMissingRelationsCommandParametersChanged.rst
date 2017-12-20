.. include:: ../../Includes.txt

======================================================================
Breaking: #78623 - Lowlevel MissingRelationsCommand parameters changed
======================================================================

See :issue:`78623`

Description
===========

The existing CLI command within EXT:lowlevel for showing relations and soft-references to non-existing records,
offline versions and records marked as deleted has been migrated to a Symfony Console command.

The command previously available via `./typo3/cli_dispatch.phpsh lowlevel_cleaner missing_relations` is now available
via `./typo3/sysext/core/bin/typo3 cleanup:missingrelations` and allows the following CLI options to be set:

`--update-refindex` - updates the reference index before scanning for missing files. If not set, the user is asked if the task should be run
`--dry-run` - do not delete the references but only list the references that are missing but connected to the TYPO3 system

The PHP class of the old CLI command `TYPO3\CMS\Lowlevel\MissingRelationsCommand` has been removed.


Impact
======

Calling the old CLI command `./typo3/cli_dispatch.phpsh lowlevel_cleaner missing_relations` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding relations pointing to deleted, offline versions or
non-existing records.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI, ext:lowlevel