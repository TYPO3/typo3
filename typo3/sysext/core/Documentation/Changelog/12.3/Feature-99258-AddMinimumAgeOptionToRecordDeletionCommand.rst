.. include:: /Includes.rst.txt

.. _feature-99258-1670017157:

=======================================================================================
Feature: #99258 - Add minimum age option to EXT:lowlevel cleanup:deletedrecords command
=======================================================================================

See :issue:`99258`

Description
===========

Using the CLI command `cleanup:deletedrecords` to clean up the database
periodically is not really possible with an installed EXT:recycler, because all
records marked for deletion are deleted immediately and thus the recycler seems
less useful.

The new option `--min-age` added to the `cleanup:deletedrecords` CLI command
allows to define a minimum age of X days a record needs to be marked as deleted
before it really gets deleted.

Impact
======

Executing `bin/typo3 cleanup:deletedrecords --min-age 30` will only delete
records that are marked for more than 30 days for deletion.

.. index:: CLI, ext:lowlevel
