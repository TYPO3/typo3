.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _technical-background:

Appendix A – Technical Background
---------------------------------

This section aims to give some more in-depth information about what
happens behind the scene in the Scheduler.

When a task is registered with the Scheduler, an instance of the task
class is created and stored in a database record as a serialized
object. The database record itself contains additional information
about the registration, mostly about past and future executions. The
theory is that all the information that is really proper to the task
should be defined as member variables of the task class and is thus
encapsulated inside the task object. The information which relates to
executing a registered task is stored in the Scheduler's database
table.

That being said, a task also contains information about its execution.
Indeed each task class has an instance of
:code:`TYPO3\CMS\Scheduler\Execution` as a member variable, which contains
information such as start and end date and is used to calculate the
next execution date.

When a task is running, its start time is stored into an array, which
is serialized and stored in the corresponding database record. If
several executions are running at the same time, the array will
contain several timestamps. Thus the "serialized\_executions" field
actually contains an array of integers and not serialized instances of
:code:`TYPO3\CMS\Scheduler\Execution` objects.


