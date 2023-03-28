.. include:: /Includes.rst.txt

.. _deprecation-100247-1679480707:

======================================================================
Deprecation: #100247 - Various interconnected methods in EXT:scheduler
======================================================================

See :issue:`100247`

Description
===========

The scheduler system extension, responsible for executing long-running, timed
or recurring tasks, has been included since TYPO3 v4.3, but never received an
overhaul of its code base.

Back then, the main :php:`\TYPO3\CMS\Scheduler\Scheduler` class and the
:php:`\TYPO3\CMS\Scheduler\Task\AbstractTask` class were the main API classes, all logic being included,
whereas :php:`AbstractTask` is the main class that all custom tasks within
extensions derive from.

However, in the past 15 years TYPO3's code base has undergone a lot of API
design changes related to separation of concerns. In order to achieve this in
the scheduler extension, almost all access to the actual database access around
task retrieving and scheduling has been moved into its own
:php:`\TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository` class.

For this reason, the following methods within the original API classes are now
either marked as deprecated or internal - not part of TYPO3's public API
anymore - as they have now been moved into the new repository class.

* :php:`Scheduler->addTask()`
* :php:`Scheduler->log()` - marked as internal
* :php:`Scheduler->removeTask()`
* :php:`Scheduler->saveTask()`
* :php:`Scheduler->fetchTask()`
* :php:`Scheduler->fetchTaskRecord()`
* :php:`Scheduler->fetchTaskWithCondition()`
* :php:`Scheduler->isValidTaskObject()`
* :php:`Scheduler->log()` - marked as internal
* :php:`AbstractTask->isExecutionRunning()`
* :php:`AbstractTask->markExecution()`
* :php:`AbstractTask->unmarkExecution()`
* :php:`AbstractTask->unmarkAllExecutions()`
* :php:`AbstractTask->save()` - marked as internal
* :php:`AbstractTask->remove()`
* :php:`AbstractTask->setScheduler()` - marked as internal
* :php:`AbstractTask->unsetScheduler()` - marked as internal
* :php:`AbstractTask->registerSingleExecution()` - marked as internal
* :php:`AbstractTask->getExecution()` - marked as internal
* :php:`AbstractTask->setExecution()` - marked as internal
* :php:`AbstractTask->getNextDueExecution()` - marked as internal
* :php:`AbstractTask->areMultipleExecutionsAllowed()` - marked as internal
* :php:`AbstractTask->stop()` - marked as internal


Impact
======

Calling any of the deprecated methods will trigger a PHP warning. Using the
internal methods should be avoided and is not covered by the TYPO3 backwards
compatibility promise.


Affected installations
======================

TYPO3 installations with extensions that include custom scheduler tasks accessing
these methods. The Extension Scanner might be helpful to detect these usages.


Migration
=========

Use the :php:`SchedulerTaskRepository` methods instead.

.. index:: PHP-API, PartiallyScanned, ext:scheduler
