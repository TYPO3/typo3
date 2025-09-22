:navigation-title: Adding / Editing

..  include:: /Includes.rst.txt
..  _adding-editing-task:

========================
Adding or editing a task
========================

Administrators can add or edit scheduler tasks in the backend module
:guilabel:`System > Scheduler`.

When adding a new scheduler task a wizard will allow you to select a task
type from several categories.

..  seealso::
    Developers can implement and register custom tasks:
    `Creating a custom scheduler task <https://docs.typo3.org/permalink/typo3-cms-scheduler:creating-tasks>`_

..  contents:: Table of contents

..  _information-screen:
..  _adding-editing-task-wizard:

The scheduler task wizard
=========================

..  figure:: /Images/EmptySchedulerModule.png
    :alt: Screenshot of an empty scheduler module, No tasks defined yet.

    Click on "New task" to add a task

..  figure:: /Images/TaskCreationWizard.png
    :alt: Screenshot of the "New task" wizard in the scheduler backend module

    Choose the task to be created

..  seealso::
    Developers can listen to event `ModifyNewSchedulerTaskWizardItemsEvent <https://docs.typo3.org/permalink/typo3-cms-scheduler:modifynewschedulertaskwizarditemsevent>`_
    to influence the items displayed here.

..  _adding-editing-task-form:

The scheduler task form
=======================

When adding or editing a task, the following form will show up:

..  figure:: /Images/AddingATask.png
    :alt: Screenshot of the form to Create new Scheduler task on root level

    Adding a new scheduled task

Some fields require additional explanations (inline help is
available by moving the mouse over the field labels):

-   A disabled task will be skipped by the command-line script. It may
    still be launched manually, as described above.

..  versionadded:: 13.3
    Similar to editing regular content elements, it is now possible to save
    scheduler tasks being edited via keyboard shortcuts as well.

It is possible to invoke the :kbd:`Ctrl`/:kbd:`Cmd` + :kbd:`s` hotkey to save a
scheduler task, altogether with the hotkey :kbd:`Ctrl`/:kbd:`Cmd` + :kbd:`Shift` + :kbd:`S`
to save and close a scheduler task.

..  _adding-editing-task-form-settings:

Scheduler task settings
=======================

Some tasks allow additional settings to be made in the area :guilabel:`Settings`.
These fields differ from task to task

..  _adding-editing-task-form-timing:

Task executions timing details
==============================

..  figure:: /Images/TaskExecutionDetails.png
    :alt: Screenshot of tab "Timing" the scheduler task form

    Choosing a frequency for a recurring task

-   A task must have a start date. It defaults to the time of creation.
    The server's time appears at the bottom of the form.

-   Task can be run a single time or recurring.

-   The frequency needs be entered only for recurring tasks.
    It can be either an integer number of seconds or a cron-like schedule expression.
    Scheduler supports ranges, steps and keywords like ``@weekly``.
    See `en.wikipedia.org <https://en.wikipedia.org/wiki/Cron#CRON_expression>`_ for more information.
    See :php:`\TYPO3\CMS\Scheduler\CronCommand\CronCommand`
    and :php:`\TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand`
    class references in the TYPO3 CMS source code for definitive rules.

-   Parallel executions are denied for recurring tasks. They can be allowed by
    checking "Allow Parallel Execution"

If an error occurs when validating a cron definition, the
Scheduler's built-in cron parser tries to provide an explanation about
what's wrong.

