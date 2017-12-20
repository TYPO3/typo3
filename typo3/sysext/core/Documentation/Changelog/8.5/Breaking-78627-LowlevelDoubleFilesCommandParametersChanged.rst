.. include:: ../../Includes.txt

======================================================================
Breaking: #78627 - Lowlevel MissingRelationsCommand parameters changed
======================================================================

See :issue:`78627`

Description
===========

The existing CLI command within EXT:lowlevel for showing files within uploads/ that are used by records twice (non-FAL)
has been migrated to a Symfony Console command.

The command previously available via `./typo3/cli_dispatch.phpsh lowlevel_cleaner double_files` is now available
via `./typo3/sysext/core/bin/typo3 cleanup:multiplereferencedfiles` and allows the following CLI options to be set:

`--update-refindex` - updates the reference index before scanning for multiple-referenced files. If not set, the user is asked if the task should be run
`--dry-run` - do not copy the files to single-reference them, but only list the references and files.

The PHP class of the old CLI command `TYPO3\CMS\Lowlevel\DoubleFilesCommand` has been removed.


Impact
======

Calling the old CLI command `./typo3/cli_dispatch.phpsh lowlevel_cleaner double_files` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding files with two records pointing to them.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI, ext:lowlevel
