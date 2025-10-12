..  include:: /Includes.rst.txt
..  _introduction:

============
Introduction
============

The Scheduler is a system extension that provides a simple interface to automate
the running of tasks in TYPO3. It provides an easier alternative to having to set
up command-line scripts in cron jobs.

..  _task-management:

Task management
===============

A task is created by extending the Scheduler base class and then registering it so
that it appears in the Scheduler backend module.

..  _screenshots:

Screenshots
===========

Below is the Scheduler backend module, showing a task that has been registered.
The module shows details about the tasks, i.e. status, type and whether it failed during the
last execution, and individual tasks can be rerun manually by clicking on the
'play' button.

..  figure:: ../Images/BackendModuleMainView.png
    :alt: Scheduler main screen

    Main screen of the Scheduler BE module

..  _tasks-execution:

Task execution
==============

The Scheduler includes a command-line script that needs to be registered once
in the server's crontab to set it up. It is run by the TYPO3 command-line
dispatcher. Every time the Scheduler is launched by the cron daemon it looks for
tasks that are due (or overdue) and executes them.

When a task is executed it is marked as being
executed in the Scheduler database record (in the
`serialized_executions` field). When the task has finished running, the
execution status is removed from the database record. This makes it
clear whether a task is currently running and also
prevents multiple executions. If a task requires
more time to run than the frequency it is set up for, a
new run will start (which is not always desirable). It is possible
to prevent such parallel (or multiple) executions.

..  _follow-up:

Follow-up
=========

Log messages are written out to the TYPO3 system :guilabel:`System > Log`
when a task starts and ends and when parallel execution has been blocked. This
provides a trace of events of the tasks.

A task that fails may also raise an exception reporting the reasons for failure.
The exception message will be logged in the Scheduler database table and
displayed in the backend module.

Tasks have no command-line output as they are designed to run in the background.

Symfony Console commands can also be run as tasks (see
`https://docs.typo3.org/permalink/t3coreapi:symfony-console-commands`__).
These tasks can specify all commandline arguments that are available to Symfony
Console commands.

..  _glossary:

Glossary
========

Task
    Specifically, a piece of code that does a precise task and can be registered
    with the Scheduler in order to execute that piece of code at a precise time,
    either once or recurrently.

Task class
    A type of task, for example, the "IP Anonymization" task is one
    particular task class. Its function is to anonymize IP addresses to enforce
    the privacy of persisted data. The "Optimize MySQL database tables"
    task executes "OPTIMIZE TABLE" statements on selected database tables.

Registered task
    An instance of a task class that has been
    registered with the Scheduler. A given task class may be registered
    several times, for example if it needs to be executed with different
    parameters.

..  _credits:

Credits
=======

The Scheduler is a derivation of the Gabriel extension originally developed by
Christian Jul Jensen and further developed by Markus Friedrich.
