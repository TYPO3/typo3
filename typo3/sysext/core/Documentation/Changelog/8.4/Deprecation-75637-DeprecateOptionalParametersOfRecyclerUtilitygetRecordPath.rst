.. include:: /Includes.rst.txt

=======================================================================================
Deprecation: #75637 - Deprecate optional parameters of RecyclerUtility::getRecordPath()
=======================================================================================

See :issue:`75637`

Description
===========

The following arguments of the method :php:`RecyclerUtility::getRecordPath` have been marked as deprecated:

- :php:`$clause`
- :php:`$titleLimit`
- :php:`$fullTitleLimit`


Impact
======

Using any of the arguments above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using custom calls to :php:`RecyclerUtility::getRecordPath` using the mentioned arguments.


Migration
=========

No migration available.

.. index:: PHP-API, Backend, ext:recycler
