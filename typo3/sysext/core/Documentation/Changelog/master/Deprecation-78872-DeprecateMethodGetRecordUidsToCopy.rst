.. include:: ../../Includes.txt

==========================================================
Deprecation: #78872 - Deprecate method getRecordUidsToCopy
==========================================================

See :issue:`78872`

Description
===========

The method :php:`getRecordUidsToCopy` is not used at any place in the TYPO3 core.


Impact
======

Calling the deprecated :php:`getRecordUidsToCopy` methods will trigger a deprecation log entry.


Affected Installations
======================

Any installation using the mentioned method :php:`getRecordUidsToCopy`


Migration
=========

No migration available.

.. index:: Backend