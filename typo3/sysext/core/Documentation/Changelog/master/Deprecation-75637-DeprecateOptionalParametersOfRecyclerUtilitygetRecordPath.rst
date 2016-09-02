
.. include:: ../../Includes.txt

=======================================================================================
Deprecation: #75637 - Deprecate optional parameters of RecyclerUtility::getRecordPath()
=======================================================================================

Description
===========

The following arguments of the method :php:`RecyclerUtility::getRecordPath` have been deprecated:

- :php:`$clause`
- :php:`$titleLimit`
- :php:`$fullTitleLimit`


Impact
======

Using any of the arguments above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using custom calls to :php:`RecyclerUtility::getRecordPath` using the mentioned arguments


Migration
=========

No migration available.