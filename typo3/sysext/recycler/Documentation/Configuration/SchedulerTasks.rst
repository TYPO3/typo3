..  include:: /Includes.rst.txt

..  _scheduler-tasks:

==============
Scheduler task
==============

The :doc:`Scheduler <ext_scheduler:Index>` system extension adds task
:guilabel:`Recycler > Remove deleted records` to permanently delete old records
from selected tables:

..  figure:: /Images/SchedulerTask.png
    :class: with-shadow
    :alt: Configuration of the scheduler task

    Configuration of the scheduler task

Create a new task with the following settings:

Delete entries older than (in days)
    Enter the number of days after which records should be deleted permanently.

Tables
    Select the tables where deletion is to be applied after the
    specified number of days.

One can define multiple tasks, for example, to remove deleted backend users and
user groups after 30 days from the database, but pages and page contents only
after 90 days.

..  seealso::
    :ref:`cli-commands`
