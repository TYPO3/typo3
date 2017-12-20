.. include:: ../../Includes.txt

===============================================================
Breaking: #78895 - Lowlevel RteImagesCommand parameters changed
===============================================================

See :issue:`78895`

Description
===========

The existing CLI command within EXT:lowlevel for detecting and removing RTE files within uploads/ which are not referenced by TYPO3
has been migrated to a Symfony Console command. The same command is also used to copy RTE images which are used multiple
times on multiple references, to be only used once.

The command previously available via `./typo3/cli_dispatch.phpsh lowlevel_cleaner rte_images` is now available via
`./typo3/sysext/core/bin/typo3 cleanup:rteimages` and allows the following CLI options to be set:

`--update-refindex` - updates the reference index before scanning for lost files. If not set, the user is asked if the task should be run
`--dry-run` - do not copy / delete the files but only list the files that are not wrongly connected or not connected at all.

The PHP class of the old CLI command `TYPO3\CMS\Lowlevel\RteImagesCommand` has been removed.


Impact
======

Calling the old CLI command `./typo3/cli_dispatch.phpsh lowlevel_cleaner rte_images` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding RTE image files.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI, ext:lowlevel, PHP-API