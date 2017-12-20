.. include:: ../../Includes.txt

===============================================================
Breaking: #78552 - Lowlevel LostFilesCommand parameters changed
===============================================================

See :issue:`78552`

Description
===========

The existing CLI command within EXT:lowlevel for detecting and removing files within uploads/ which are not referenced by TYPO3
has been migrated to a Symfony Console command.

The command previously available via `./typo3/cli_dispatch.phpsh lowlevel_cleaner lost_files` is now available via
`./typo3/sysext/core/bin/typo3 cleanup:lostfiles` and allows the following CLI options to be set:

`--update-refindex` - updates the reference index before scanning for lost files. If not set, the user is asked if the task should be run
`--exclude=uploads/mypics/,uploads/psa` - a list of paths of files to exclude within uploads/
`--dry-run` - do not delete the files but only list the files that are not connected to the TYPO3 system anymore

The PHP class of the old CLI command `TYPO3\CMS\Lowlevel\LostFilesCommand` has been removed.


Impact
======

Calling the old CLI command `./typo3/cli_dispatch.phpsh lowlevel_cleaner lost_files` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding and deleting lost files.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI, ext:lowlevel
