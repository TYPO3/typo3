.. include:: /Includes.rst.txt

.. _breaking-98489-1664577935:

====================================================
Breaking: #98489 - Removal of SleepTask and TestTask
====================================================

See :issue:`98489`

Description
===========

Previous TYPO3 installation contained the task `SleepTask` and `TestTask` which
served as examples when scheduler was introduced in 2009.

The tasks have been removed without substitution.

Impact
======

The scheduler tasks are not available anymore and won't be executed.

Affected installations
======================

All TYPO3 installations relying on these scheduler tasks.

Migration
=========

If used, the tasks can be removed within the scheduler module.

The configuration :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['scheduler']['showSampleTasks']`
is removed automatically.

.. index:: PHP-API, NotScanned, ext:scheduler
