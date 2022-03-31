.. include:: /Includes.rst.txt

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

The following options can be set:

- :bash:`--action={nameofaction}` to clean up versioned records, one of the following actions are possible:

  - "versions_in_live": Delete versioned records in the live workspace

  - "published_versions": Delete versions of published records

  - "invalid_workspace": Move records inside a non-existing workspace ID into the live workspace

  - "unused_placeholders": Remove placeholders which are not used anymore from the database

- :bash:`-v` and :bash:`-vv` to show more detailed information on the records affected

- :bash:`--pid=23` or :bash:`-p=23` to only find versions with page ID 23 (otherwise "0" is taken)

- :bash:`--depth=4` or :bash:`-d=4` to only clean recursively until a certain page tree level.

- :bash:`--dry-run` to only show the records to be changed / deleted

The PHP class of the old CLI command :php:`TYPO3\CMS\Lowlevel\VersionsCommand` has been removed.


Impact
======

Calling the old CLI command :bash:`./typo3/cli_dispatch.phpsh lowlevel_cleaner versions` will result in an error message.


Affected Installations
======================

Any TYPO3 instances using the lowlevel cleaner for finding and cleaning up versioned records.


Migration
=========

Update the CLI call on your servers to the new command line and available options as shown above.

.. index:: CLI, ext:lowlevel
