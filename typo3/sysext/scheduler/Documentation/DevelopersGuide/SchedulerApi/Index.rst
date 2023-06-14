..  include:: /Includes.rst.txt

..  _scheduler-api:

=============
Scheduler API
=============

It is possible to refer to the Scheduler from other extensions. Once a
:php:`\TYPO3\CMS\Scheduler\Scheduler` object has been instantiated all of its
public methods can be used. The PHPdoc of the methods should be enough to
understand what each is to be used for.

The extension ships with a
:php:`\TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository` class,
which provides some helpful methods, for example:

*   :php:`findByUid(int $uid)`: this method is used to fetch a registered task
    from the database given an ID.

*   :php:`findNextExecutableTask()`: this method returns the next due task. The
    return value is the unserialized task object.

*   :php:`findRecordByUid(int $uid)`: is also used to retrieve a registered task
    from the database, but it returns the record corresponding to the task
    registration and not the task object itself.

These are the main methods that will be used from outside the
Scheduler as they can retrieve registered tasks from the database.
When a task has been fetched, all public methods from the
:php:`\TYPO3\CMS\Scheduler\Task\AbstractTask` class can be used.
