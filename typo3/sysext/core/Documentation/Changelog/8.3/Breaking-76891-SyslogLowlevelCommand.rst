
.. include:: ../../Includes.txt

==========================================
Breaking: #76891 - syslog lowlevel command
==========================================

See :issue:`76891`

Description
===========

The lowlevel cleaner syslog command has been migrated to a Symfony Command to show the latest sys_log entries on the command line.

The new command can be called via `./typo3/sysext/core/bin/typo3 syslog:list`.


Impact
======

Calling `./typo3/cli_dispatch.phpsh lowlevel_cleaner syslog -r` will not show the expected output anymore as before.


Migration
=========

Use `./typo3/sysext/core/bin/typo3 syslog:list` with the optional verbose parameter instead.

.. index:: CLI, ext:lowlevel
