..  include:: /Includes.rst.txt
..  _technical-background:
..  _scheduler-task-storage:

======================
Scheduler task storage
======================

..  versionchanged:: 14.0
    With TYPO3 v14.0 the storage of scheduler tasks switched from the
    PHP-serialized storage format in the database to a JSON-based format.

The scheduler tasks displayed in backend module :guilabel:`Administration > Scheduler`
are stored in the **database** table :sql:`tx_scheduler_task`. Task groups are
stored in table :sql:`tx_scheduler_task_group`.

..  contents:: Table of contents

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
`priority`
    The :ref:`priority <adding-editing-task-form-priority>` of the task as an
    integer: `150` (High), `100` (Regular, default), or `50` (Low).

Additionally it stores some information on the last and next execution of the
task, and fields for a description and the group.

..  _task-storage-priority:

Task priority levels
====================

..  versionadded:: 14.2
    :changelog: feature-109110-1742558400

The field `priority` is a plain integer column. Any positive integer is a
valid priority. To add a priority level, add an item to the TCA of the
field:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_task.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addTcaSelectItem(
      'tx_scheduler_task',
      'priority',
      [
        'label' => 'my_extension.messages:priority.critical',
        'value' => 200,
      ],
      '150',
      'after',
    );

Tasks with a value above `150` are executed before tasks with the priority
**High**. Tasks with a value below `50` are executed after tasks with
the priority **Low**.

The Scheduler module shows the label of the TCA item in the task list. The
`label` must be a valid language label. If no TCA item matches the value,
the module shows the integer.

..  _serialized-objects:
..  _save-task-state:
..  _serialized-objects_migration:

Migration from serialized task objects
======================================

..  attention::

    ..  versionchanged:: 14.0

        The storage format was changed with TYPO3 v14. If the database field
        :sql:`tx_scheduler_task:tasktype` is empty this hints at a missing or failed
        migration by the upgrade wizard. See also:
        `Important: #106532 - Changed database storage format for Scheduler Tasks <https://docs.typo3.org/permalink/changelog:important-106532-1744207039>`_

        A manual migration is necessary. You can also delete the task and
        create it newly.
