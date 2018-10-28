.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _scheduler-api:

Scheduler API
^^^^^^^^^^^^^

It is possible to refer to the Scheduler from other extensions. Once a
:code:`\TYPO3\CMS\Scheduler\Scheduler` object has been instantiated all of its public
methods can be used. The PHPdoc of the methods should be enough to
understand what each is to be used for. It would be excessive to
describe them all here.

However a few deserve a special mention:

- :code:`fetchTask()` : this method is used to fetch a registered task
  from the database given an id. If no id is given, it will return the
  next due task. The return value is the unserialized task object.

- :code:`fetchTaskRecord()` : is also used to retrieve a registered task
  from the database, but it returns the record corresponding to the task
  registration and not the task object itself. It is not designed fetch
  the next due task.

- :code:`fetchTasksWithCondition()` : can be used to retrieve one or
  more registered tasks, that fit a given SQL condition. It returns an
  array containing all the records of the matching tasks registrations.

These are the main methods that will be used from outside the
Scheduler as they can retrieve registered tasks from the database.
When a task has been fetched, all public methods from the
:code:`\TYPO3\CMS\Scheduler\Task\AbstractTask` class can be used.

