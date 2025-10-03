:navigation-title: Manual Execution

..  include:: /Includes.rst.txt
..  _manually-executing-a-task:
..  _executing-a-task-on-next-cronjob:

===========================================================
Manually executing a task from the Scheduler backend module
===========================================================

You can manually execute tasks from the BE module. After execution, each
task shows success or failure.

*   If a task was overdue, a new execution date is calculated.
*   If it was not overdue, the existing next execution date remains.

Running tasks:

*   To run a single task, press the button in its row.
*   To run multiple tasks, select their checkboxes and press the button below the list.

There are two options:

*   Run the task immediately. (Button 2 in the screenshot)
*   Schedule the task. (Button 1 in the screenshot) The selected tasks will
    then run on the next cron job.

..  figure:: /Images/ManualExecution.png
    :alt: Scheduler backend module with the buttons "Run task on next cron job" (1) and "Run task" (2) highlighted

    Button 2 runs the task immediately, while button 1 schedules it fot the next cronjob run
