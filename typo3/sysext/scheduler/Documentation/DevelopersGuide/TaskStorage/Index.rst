..  include:: /Includes.rst.txt
..  _scheduler-task-storage:

======================
Scheduler task storage
======================

..  versionchanged:: 14.0
    With TYPO3 v14.0 the storage of scheduler tasks switched from the
    PHP-serialized storage format in the database to a JSON-based format.

The scheduler tasks displayed in backend module :guilabel:`System > Scheduler`
are stored in the **database** table :sql:`tx_scheduler_task`. Task groups are
stored in table :sql:`tx_scheduler_task_group`.

..  _scheduler-task-storage-fields:

tx_scheduler_task fields
========================

The database table :sql:`tx_scheduler_task` contains the following fields:

`tasktype`
    Typically contains the fully qualified class name of the task, for example
    :php:`\TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask` or
    the command name, for example `language:update` if the task is implemented as
    `Symfony console command <https://docs.typo3.org/permalink/t3coreapi:symfony-console-commands>`_.
`parameters`
    The options configuring the task as JSON-encoded value.
`execution_details`
    Contains all details on execution like the type (Recurring / Single),
    start and end dates and the frequency in which recurring tasks should be
    executed.

Additionally it stores some information on the last and next execution of the
task, and fields for a description and the group.

..  attention::

    ..  versionchanged:: 14.0

        The storage format was changed with TYPO3 v14. If the database field
        :sql:`tx_scheduler_task:tasktype` is empty this hints at a missing or failed
        migration by the upgrade wizard. See also:
        `Important: #106532 - Changed database storage format for Scheduler Tasks <https://docs.typo3.org/permalink/changelog:important-106532-1744207039>`_

        A manual migration is necessary. You can also delete the task and
        create it newly.
