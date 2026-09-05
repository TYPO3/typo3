..  include:: /Includes.rst.txt

..  _breaking-110637-1788640608:

====================================================================
Breaking: #110637 - Scheduler repository throws InvalidTaskException
====================================================================

See :issue:`110637`

Description
===========

The :php:`SchedulerTaskRepository::findByUid()` and
:php:`SchedulerTaskRepository::findNextExecutableTask()` methods now throw
:php:`\TYPO3\CMS\Scheduler\Exception\InvalidTaskException` when a Scheduler
task cannot be deserialized or is otherwise invalid. Previously, the global
:php:`\UnexpectedValueException` was thrown.

Impact
======

Code that catches :php:`\UnexpectedValueException` when retrieving invalid
Scheduler tasks will no longer catch the exception.

Affected installations
======================

Installations are affected when custom extension code directly uses
:php:`SchedulerTaskRepository` and handles invalid tasks by catching
:php:`\UnexpectedValueException`.

Migration
=========

Catch :php:`InvalidTaskException` instead of :php:`\UnexpectedValueException`.

Before:

..  code-block:: php

    try {
        $task = $schedulerTaskRepository->findByUid($taskUid);
    } catch (\UnexpectedValueException) {
        // Handle the invalid task.
    }

After:

..  code-block:: php

    use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;

    try {
        $task = $schedulerTaskRepository->findByUid($taskUid);
    } catch (InvalidTaskException) {
        // Handle the invalid task.
    }

Apply the same change when calling :php:`findNextExecutableTask()`.

..  index:: CLI, PHP-API, NotScanned, ext:scheduler
