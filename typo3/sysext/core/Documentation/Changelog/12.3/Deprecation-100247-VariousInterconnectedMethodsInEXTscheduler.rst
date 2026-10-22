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
whereas :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask` is the main class that all custom tasks within
extensions derive from.

However, in the past 15 years TYPO3's code base has undergone a lot of API
design changes related to separation of concerns. In order to achieve this in
the scheduler extension, almost all access to the actual database access around
task retrieving and scheduling has been moved into its own
:php:`\TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository` class.

For this reason, the following methods within the original API classes are now
either marked as deprecated or internal - not part of TYPO3's public API
anymore - as they have now been moved into the new repository class.

* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->addTask()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->log()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->removeTask()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->saveTask()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->fetchTask()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->fetchTaskRecord()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->fetchTaskWithCondition()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->isValidTaskObject()`
* :php-short:`\TYPO3\CMS\Scheduler\Scheduler->log()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->isExecutionRunning()`
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->markExecution()`
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->unmarkExecution()`
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->unmarkAllExecutions()`
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->save()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->remove()`
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->setScheduler()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->unsetScheduler()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->registerSingleExecution()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->getExecution()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->setExecution()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->getNextDueExecution()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->areMultipleExecutionsAllowed()` - marked as internal
* :php-short:`\TYPO3\CMS\Scheduler\Task\AbstractTask->stop()` - marked as internal


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

Use the :php-short:`\TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository` methods instead.

.. index:: PHP-API, PartiallyScanned, ext:scheduler
