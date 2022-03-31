
.. include:: /Includes.rst.txt

======================================================================
Feature: #67228 - Emit Signal when an IndexRecord is marked as missing
======================================================================

See :issue:`67228`

Description
===========

The new signal `recordMarkedAsMissing` is emitted when the FAL indexer encounters a sys_file record
which does not have a corresponding filesystem entry and marks it as missing.
It passes the sys_file record uid.


Impact
======

This can be used by extensions that provide or extend file management capabilities
(versioning, synchronizations, recovery etc).


.. index:: PHP-API, FAL, Backend
