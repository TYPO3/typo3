.. include:: ../../Includes.txt

==============================================================
Breaking: #79109 - Lowlevel VersionsCommand parameters changed
==============================================================

See :issue:`79109`

Description
===========

The existing CLI command within EXT:lowlevel for showing and cleaning up versions (from EXT:version / EXT:workspaces)
has been migrated to a Symfony Console command.

The command previously available via `./typo3/cli_dispatch.phpsh lowlevel_cleaner versions` is now available
via `./typo3/sysext/core/bin/typo3 cleanup:versions` and allows the following CLI options to be set:

The following options can be set
`--action={nameofaction}` to clean up versioned records, one of the following actions are possible:
   "versions_in_live": Delete versioned records in the live workspace
   "published_versions": Delete versions of published records
   "invalid_workspace": Move records inside a non-existing workspace ID into the live workspace
   "unused_placeholders": Remove placeholders which are not used anymore from the database
`-v` and `-vv` to show more detailed information on the records affected
`--pid=23` or `-p=23` to only find versions with page ID 23 (otherwise "0" is taken)
`--depth=4` or `-d=4` to only clean recursively until a certain page tree level.
`--dry-run` to only show the records to be changed / deleted

The PHP class of the old CLI command `TYPO3\CMS\Lowlevel\VersionsCommand` has been removed.


Impact
======

Calling the old CLI command `./typo3/cli_dispatch.phpsh lowlevel_cleaner versions` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding and cleaning up versioned records.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI
