.. include:: ../../Includes.txt

================================================================
Breaking: #78439 - Lowlevel FlexForm Cleaning parameters changed
================================================================

See :issue:`78439`

Description
===========

The CleanFlexFormsRecordsCommand is now using Symfony Console. The new command behaves like the old functionality,
but uses certain different parameters. It can now be called with the following CLI command:

`./typo3/sysext/core/bin/typo3 cleanup:flexforms`

The following options can be set
`--dry-run` to only show the deleted records
`-v` and `-vv` to show additional information
`--pid=23` or `-p=23` to only find and clean up records with FlexForm XMLs below page ID 23 (otherwise "0" is taken)
`--depth=4` or `-d=4` to only clean recursively until a certain page tree level.

The PHP class `TYPO3\CMS\Lowlevel\CleanFlexformCommand` has been removed.

Impact
======

Calling `typo3/cli_dispatch.phpsh lowlevel cleaner cleanflexform` will not work anymore.

Calling the PHP class results in a fatal PHP error.


Affected Installations
======================

Any TYPO3 installation using the previously command callable via `cli_dispatch.phpsh` or the related PHP class.


Migration
=========

Use the new CLI command as shown above.

.. index:: CLI, FlexForm, ext:lowlevel
