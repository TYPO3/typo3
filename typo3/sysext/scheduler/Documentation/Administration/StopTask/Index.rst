:navigation-title: Stopping a Task

..  include:: /Includes.rst.txt
..  _stopping-a-task:

===============================================
Stopping a task in the Scheduler backend module
===============================================

A task is marked as "running" while it runs. If the process crashes or
is killed, the task may remain marked as "running". This is usually
cleaned up automatically based on the maximum lifetime parameter,
but manual cleanup may sometimes be needed.

..  figure:: /Images/StoppingATask.png
    :alt: Stopping a task

    Stopping a running task from the main screen

Use the **stop** button to clear the execution mark for a task.
This allows the task to run again.

Note: This does **not** terminate an actual running or hanging process.
