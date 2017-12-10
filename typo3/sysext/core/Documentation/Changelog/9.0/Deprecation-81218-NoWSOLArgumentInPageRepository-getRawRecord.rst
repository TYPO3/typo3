.. include:: ../../Includes.txt

=====================================================================
Deprecation: #81218 - noWSOL argument in PageRepository->getRawRecord
=====================================================================

See :issue:`81218`

Description
===========

The method :php:`PageRepository->getRawRecord()` has a fourth parameter called :php:`$noWSOL` which allowed
to disable the logic for getting the workspace-related record. This method argument was previously
only used internally within PageRepository, and using this argument left the functionality of this
method to only do a simple SQL statement, which can be implemented itself without using this API call.


Impact
======

Calling :php:`PageRepository->getRawRecord()` with a fourth parameter will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance with custom extensions that use this method with a fourth parameter explicitly.


Migration
=========

Remove the fourth parameter if set to false, if just a simple SQL call is needed, implement the SQL
call directly in your PHP code.

.. index:: Frontend, PHP-API, FullyScanned
