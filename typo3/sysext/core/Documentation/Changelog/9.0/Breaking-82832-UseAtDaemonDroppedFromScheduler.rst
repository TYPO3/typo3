.. include:: /Includes.rst.txt

=======================================================
Breaking: #82832 - Use at daemon dropped from scheduler
=======================================================

See :issue:`82832`

Description
===========

The functionality to execute tasks via the unix "at daemon" (atd)
has been dropped.

The following method has been dropped:

* :php:`TYPO3\CMS\Scheduler\Scheduler->scheduleNextSchedulerRunUsingAtDaemon()`


Impact
======

If this feature has been used, existing tasks may not be executed anymore.


Affected Installations
======================

The feature "useAtdaemon" had to be explicitly enabled in scheduler
extension configuration. In general it was very sparsely used.


Migration
=========

Switch to cron execution instead.

.. index:: Backend, CLI, PHP-API, PartiallyScanned, ext:scheduler
