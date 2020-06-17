.. include:: ../../Includes.txt

========================================================
Feature: #84549 - Usage of new REST API on get.typo3.org
========================================================

See :issue:`84549`

Description
===========

Instead of providing only a JSON file, the get.typo3.org website was refactored to provide a REST web API for
information on TYPO3 releases.

The core uses that information to check for available upgrades and download new versions.
With this change the information will be fetched via the new API.


Impact
======

* :php:`CoreVersionService` makes use of the REST API directly - no complete version listing
  is stored in the registry anymore as the new API provides direct access to necessary information
* The reports module contains a message hinting at the availability of updates.

.. index:: Backend, PHP-API, ext:install
