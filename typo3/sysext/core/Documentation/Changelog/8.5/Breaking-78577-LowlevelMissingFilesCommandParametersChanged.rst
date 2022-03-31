.. include:: /Includes.rst.txt

==================================================================
Breaking: #78577 - Lowlevel MissingFilesCommand parameters changed
==================================================================

See :issue:`78577`

Description
===========

The existing CLI command within EXT:lowlevel for showing missing files that are referenced by TYPO3 records
has been migrated to a Symfony Console command.

The command previously available via `./typo3/cli_dispatch.phpsh lowlevel_cleaner missing_files` is now available via
`./typo3/sysext/core/bin/typo3 cleanup:missingfiles` and allows the following CLI options to be set:

`--update-refindex` - updates the reference index before scanning for missing files. If not set, the user is asked if the task should be run
`--dry-run` - do not delete the references, files but only list the files that are missing but connected to the TYPO3 system

The PHP class of the old CLI command `TYPO3\CMS\Lowlevel\MissingFilesCommand` has been removed.


Impact
======

Calling the old CLI command `./typo3/cli_dispatch.phpsh lowlevel_cleaner missing_files` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding missing files in relations.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI, ext:lowlevel
