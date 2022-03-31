.. include:: /Includes.rst.txt

======================================================================
Breaking: #78520 - Lowlevel Orphan Records Cleaning parameters changed
======================================================================

See :issue:`78520`

Description
===========

The OrphanRecordsCommand is now using Symfony Console. The new command behaves like the old functionality,
but uses certain different parameters. It can now be called with the following CLI command:

`./typo3/sysext/core/bin/typo3 cleanup:orphanrecords`

The following options can be set
`--dry-run` to only show the orphaned records
`-v` and `-vv` to show additional information

The PHP class `TYPO3\CMS\Lowlevel\OrphanRecordsCommand` has been removed.


Impact
======

Calling `typo3/cli_dispatch.phpsh lowlevel cleaner orphan_records` will not work anymore.

Calling the PHP class results in a fatal PHP error.


Affected Installations
======================

Any TYPO3 installation using the previously command callable via `cli_dispatch.phpsh` or the related PHP class.


Migration
=========

Use the new CLI command as shown above.

.. index:: CLI, ext:lowlevel
