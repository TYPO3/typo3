.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _overview:

Overview
--------


.. _why-a-scheduling-tool:

Why a scheduling tool?
^^^^^^^^^^^^^^^^^^^^^^

When running and maintaining a complex system like TYPO3, there are a
number of tasks that need to be automated or executed in the small
hours, when no one is around to press the button. Indeed quite a few
extensions come with some command-line scripts meant to be run
regularly using cron jobs. When each of these scripts need their
separate entry in the server's crontab, maintenance complexity
increases, as well as the cost of migration. Furthermore there's no
simple way to keep an overview of and manage these tasks inside TYPO3.

The Scheduler aims to address this issue.


.. _tasks-management:

Tasks management
^^^^^^^^^^^^^^^^

Scripts can be developed as Scheduler tasks by extending a base class
provided by the Scheduler. They can then be registered with the
Scheduler. At that point it becomes possible to set up a schedule for
them by using the Scheduler's BE module.

The BE module provides an overview of all scheduled tasks and some
indication of their status, e.g. are they currently running or are
they late, did something wrong happen during last execution, etc. It
is also possible to manually start the execution of tasks from the BE
module.


.. _tasks-execution:

Tasks execution
^^^^^^^^^^^^^^^

The Scheduler provides a command-line tool to be run by TYPO3's
command-line dispatcher. Only this script needs to be registered in
the server's cron tab for all other recurring tasks to be executed.
Indeed every time the Scheduler is launched by the cron daemon, it
will look for all tasks that are due (or overdue) and execute them.

When a task is executed by the Scheduler it is marked as being
executed in the corresponding database record (in the field called
"serialized\_executions"). When the task has finished running, the
execution is removed from the database record. This mechanism makes it
possible to know that a given task is currently running and also helps
prevent multiple executions. Indeed it may be that a task requires
more time to run than the frequency it is set up for. In such a case a
new run may be started which is not always desirable. It is possible
to deny such parallel (or multiple) executions.


.. _follow-up:

Follow-up
^^^^^^^^^

Whenever a task starts or ends a message is written to TYPO'3 system
log (viewable in the Admin Tools > Log module). A message is also
written when a parallel execution has been blocked. This makes it
possible to follow what happens, since the main purpose of the
Scheduler is to run things when nobody is watching.

A task that fails may report on the reasons for failure using
exceptions. Such a message will be logged in the Scheduler's database
table and will be displayed in the BE module.

There's no default output to the command-line as scheduled tasks are
designed to run in the background.


.. _glossary:

Glossary
^^^^^^^^

A few terms need to be defined more precisely:

- **Task** : this word is used quite generally throughout this document,
  sometimes to cover different meanings. A task is really a piece of
  code that does a precise task and can be registered with the Scheduler
  in order to execute that piece of code at a precise time, recurrently
  or not.

- **Task class** : this is the type of task. The "test" task is one
  particular task class. Its function is to send an email. The "sleep"
  task is another task class.

- **Registered task** : an instance of a task class that has been
  registered with the Scheduler. A given task class may be registered
  several times, for example if it needs to be executed with different
  parameters.

