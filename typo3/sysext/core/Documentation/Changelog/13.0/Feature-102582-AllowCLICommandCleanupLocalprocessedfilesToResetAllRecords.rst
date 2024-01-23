.. include:: /Includes.rst.txt

.. _feature-102582-1701678139:

======================================================================================
Feature: #102582 -  Allow CLI command cleanup:localprocessedfiles to reset all records
======================================================================================

See :issue:`102582`

Description
===========

A new CLI Symfony command option :bash:`--all` is added to the
CLI command :bash:`bin/typo3 cleanup:localprocessedfiles` that allows
to reset all entries in the database to force re-creating processed
files.

When developing :abbr:`FAL (File Abstraction Layer)` features or updating installations with large
user-generated content in :file:`fileadmin` storages, it may be helpful to
clean the :sql:`sys_file_processedfile` database table completely to
force a rebuild (i.e. when new FAL processors are added).

This table holds all locally generated processed files with
specific crop or size variants (or references to unaltered
originals, or "proxy" entries).

The new command option will also report the numbers of deleted records
before execution, and allows you to review execution. It is
not set by default.

Impact
======

It is now possible to use the :bash:`--all` CLI command option for
:bash:`bin/typo3 cleanup:localprocessedfiles` that allows to not
only clear missing processed files, but also existing ones.

.. index:: Backend, CLI, NotScanned, ext:lowlevel
