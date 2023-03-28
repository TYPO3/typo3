.. include:: /Includes.rst.txt

.. _feature-99258-1670017157:

=======================================================================================
Feature: #99258 - Add minimum age option to EXT:lowlevel cleanup:deletedrecords command
=======================================================================================

See :issue:`99258`

Description
===========

Using the CLI command `cleanup:deletedrecords` to clean up the database
periodically is not really possible with EXT:recycler, because all
records marked for deletion are deleted immediately and thus the recycler seems
less useful.

The new option `--min-age` added to the `cleanup:deletedrecords` CLI command
allows a minimum age of the X days that a record needs to be marked as deleted
before it really gets deleted to be defined.

Impact
======

Executing `bin/typo3 cleanup:deletedrecords --min-age 30` will only delete
records that have been marked for more than 30 days for deletion.

.. index:: CLI, ext:lowlevel
