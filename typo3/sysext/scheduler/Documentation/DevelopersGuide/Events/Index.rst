:navigation-title: Events

..  include:: /Includes.rst.txt
..  _scheduler-events:

==========================================
Events provided by the scheduler extension
==========================================

The system extension :composer:`typo3/cms-scheduler` provides the following
events:

..  contents:: Table of contents

..  _ModifyNewSchedulerTaskWizardItemsEvent:

ModifyNewSchedulerTaskWizardItemsEvent
======================================

The PSR-14 event :php:`\TYPO3\CMS\Scheduler\Event\ModifyNewSchedulerTaskWizardItemsEvent`
allows extensions to modify the items in the
`scheduler task wizard <https://docs.typo3.org/permalink/typo3/cms-scheduler:adding-editing-task-wizard>`_.

..  seealso::

    `ModifyNewSchedulerTaskWizardItemsEvent (TYPO3 explained) <https://docs.typo3.org/permalink/t3coreapi:ModifyNewSchedulerTaskWizardItemsEvent>`_
    for examples and the api information.

..  _AfterTaskExecutionEvent:

AfterTaskExecutionEvent
=======================

The PSR-14 event :php:`\TYPO3\CMS\Scheduler\Event\AfterTaskExecutionEvent`
is dispatched after a scheduled task (including command tasks) has been executed.
It provides the following information:

*   :php:`getTask(): AbstractTask` — the executed task object
*   :php:`isSuccess(): bool` — whether the task completed without exception
*   :php:`getException(): ?\Throwable` — the thrown exception on failure, or :php:`null` on success

Example listener::

    use TYPO3\CMS\Scheduler\Event\AfterTaskExecutionEvent;

    final class SchedulerTaskResultListener
    {
        public function __invoke(AfterTaskExecutionEvent $event): void
        {
            $task = $event->getTask();
            $status = $event->isSuccess() ? 'success' : 'failure';
            // e.g. send a notification, write to a custom log, etc.
        }
    }
