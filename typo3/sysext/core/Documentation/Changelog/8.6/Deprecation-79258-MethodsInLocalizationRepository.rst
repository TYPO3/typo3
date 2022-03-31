.. include:: /Includes.rst.txt

===================================================================================================================
Deprecation: #79258 - Methods getRecordLocalization() and getPreviousLocalizedRecordUid() in LocalizationRepository
===================================================================================================================

See :issue:`79258`

Description
===========

The methods :php:`LocalizationRepository::getRecordLocalization()` and :php:`LocalizationRepository::getPreviousLocalizedRecordUid()`
have been marked as deprecated as they are not used in the core anymore, since https://review.typo3.org/#/c/47645/ was merged.


Impact
======

Calling these methods will trigger a deprecation log entry. Code using them will work until these methods are removed in TYPO3 v9.


Affected Installations
======================

Any installation using the mentioned methods :php:`LocalizationRepository::getRecordLocalization()`
and :php:`LocalizationRepository::getPreviousLocalizedRecordUid()`.


Migration
=========

No migration available.

.. index:: PHP-API
