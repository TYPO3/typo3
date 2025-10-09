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

..  _stopping-a-task-cli:

Stopping a task via console command
===================================

You can also use a command to stop the task:

..  tabs::

    ..  group-tab:: Composer mode

        ..  code-block:: bash

            # Note the id of the task
            vendor/bin/typo3 scheduler:list

            vendor/bin/typo3 scheduler:run --task=<taskUid> --stop

    ..  group-tab:: Classic mode

        ..  code-block:: bash

            # Find the id of the task
            typo3/sysext/core/bin/typo3 scheduler:list

            typo3/sysext/core/bin/typo3 scheduler:run --task=<taskUid> --stop

..  _kill-task:

How to handle a truly "hung" task
=================================

If a task hangs or is stuck (for example due to an infinite loop or external I/O),
then stopping it via TYPO3 (either UI or CLI) will only clear TYPO3’s internal flag.

But the actual PHP process that’s running the task will continue on the system
until it finishes or the OS/PHP process manager kills it.

If a task keeps running indefinitely:

#.  Identify the process (via ps, top, or your process manager — for example, systemd or cron).
#.  Manually terminate the PHP process (for example, kill <pid>).
#.  Then run: :command:`typo3 scheduler:run --task=<taskUid> --stop`

..  warning::
    Manually terminating a scheduler process using `sudo kill <pid>` should only
    be used as a *last resort*.

    Killing a running PHP process may interrupt database or file operations and
    leave the system in an inconsistent state.

    Always analyze why the task is hanging before killing it.

..  code-block:: bash

    ps aux | grep scheduler

    # you might find something like this:
    www-data  12345  99.0  5.2  php vendor/bin/typo3 scheduler:run

    # To force-stop that OS-level process:
    sudo kill 12345

    # Then clear the mark again via:
    vendor/bin/typo3 scheduler:run --task=13 --stop
