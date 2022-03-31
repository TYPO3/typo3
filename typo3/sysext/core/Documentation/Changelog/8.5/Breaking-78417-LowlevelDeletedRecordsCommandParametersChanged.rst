.. include:: /Includes.rst.txt

====================================================================
Breaking: #78417 - Lowlevel DeletedRecordsCommand parameters changed
====================================================================

See :issue:`78417`

Description
===========

The DeletedRecordsCommand is now using Symfony Console. The new command behaves like the old one, but allows using certain
parameters and is located under the following path now:

`./typo3/sysext/core/bin/typo3 cleanup:deletedrecords`

The following options can be set
`--dry-run` to only show the deleted records
`-v` and `-vv` to show additional information
`--pid=23` or `-p=23` to only find and delete records below page ID 23 (otherwise "0" is taken)
`--depth=4` or `-d=4` to only delete recursively until a certain page tree level.

The PHP class `TYPO3\CMS\Lowlevel\DeletedRecordsCommand` has been removed.


Impact
======

Calling `typo3/cli_dispatch lowlevel cleaner deleted` will not work anymore.

Calling the PHP class results in a fatal PHP error.


Affected Installations
======================

Any TYPO3 installation using the old CLI command or the related PHP class.


Migration
=========

Use the new CLI command as shown above.

.. index:: CLI, ext:lowlevel
