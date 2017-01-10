.. include:: ../../Includes.txt

======================================================================================================================
Deprecation: #79258 - Deprecate LocalizationRepository getRecordLocalization and getPreviousLocalizedRecordUid methods
======================================================================================================================

See :issue:`79258`

Description
===========

After the change https://review.typo3.org/#/c/47645/ was merged
methods :php:`LocalizationRepository::getRecordLocalization()` and :php:`LocalizationRepository::getPreviousLocalizedRecordUid()`
are not used in the core any more, so they has been marked as deprecated.


Impact
======

Calling these methods will trigger a deprecation log entry. Code using them will work until these methods are removed in TYPO3 v9.


Affected Installations
======================

Any installation using the mentioned methods :php:`LocalizationRepository::getRecordLocalization()` and :php:`LocalizationRepository::getPreviousLocalizedRecordUid()`.


Migration
=========

No migration available.

.. index:: PHP-API