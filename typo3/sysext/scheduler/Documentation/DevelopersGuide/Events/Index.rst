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
