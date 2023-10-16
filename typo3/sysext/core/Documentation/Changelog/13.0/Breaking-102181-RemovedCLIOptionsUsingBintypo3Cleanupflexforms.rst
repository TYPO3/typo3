.. include:: /Includes.rst.txt

.. _breaking-102181-1697467272:

===========================================================================
Breaking: #102181 - Removed CLI options using `bin/typo3 cleanup:flexforms`
===========================================================================

See :issue:`102181`

Description
===========

The CLI command :bash:`bin/typo3 cleanup:flexforms` of extension :php:`lowlevel`
can be used to clean up database record :php:`type="flex"` fields that contain values
not reflected in the current FlexForm data structure anymore.

The command has been changed slightly: The CLI options :bash:`-p` / :bash:`--pid`
and :bash:`-d` / :bash:`--depth` have been removed.

The "dry run" CLI option :bash:`--dry-run` is kept.

The command implementation has been rewritten in TYPO3 v13 and is some orders
of magnitudes quicker than before: While the command could easily run hours for
a seasoned instance, it is now usually a matter of seconds. The "pid" and
"depth" options were a hindrance to this drastic performance improvement and
have been removed.


Impact
======

The command exits with an error when called with one of :bash:`-p`, :bash:`--pid`,
:bash:`-d` or :bash:`--depth` option. It is no longer possible to restrict the
command to single page tree sections, the command always checks all (not soft-deleted)
records.


Affected installations
======================

The command is not very well known and - if ever - often only used when deploying
major upgrades of TYPO3 instances. Instances using one of the above options should
remove them from their deployment scripts, and enjoy the massive speed improvement.


Migration
=========

No migration, remove the above mentioned options.

.. index:: CLI, FlexForm, NotScanned, ext:lowlevel
