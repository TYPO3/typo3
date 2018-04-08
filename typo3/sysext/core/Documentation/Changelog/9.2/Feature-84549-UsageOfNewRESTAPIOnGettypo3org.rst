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
With this change the information will be fetched via the new API. Additionally information about new releases will also
be displayed in the system information toolbar, both in Composer Mode and Classic Mode mode to notify users that TYPO3
might be updated. If the version is out-of-support or has known security issues, the notification is displayed as an
error.


Impact
======

* :php:`CoreVersionService` makes use of the REST API directly - no complete version listing
  is stored in the registry anymore as the new API provides direct access to necessary information
* The system information toolbar contains a message hinting at the availability of updates - the
  message is purposely also displayed for editors as they are exposed to the system more often
  and will be able to quickly notify administrators in case security relevant updates are released.

.. index:: Backend, PHP-API, ext:install
