:navigation-title: Task development

..  include:: /Includes.rst.txt
..  _creating-tasks:

================================
Creating a custom scheduler task
================================

..  important::
    ..  versionchanged:: 14.0
        Custom scheduler tasks can be registered as TCA types in table
        `tx_scheduler_task`.

        See also: `Changelog Feature: #107526 - Custom TCA types for scheduler tasks <https://docs.typo3.org/permalink/changelog:feature-107526-1747816234>`_.

..  contents:: Table of contents

..  toctree::
    :glob:
    :titlesonly:

    *

..  seealso::
    Symfony console commands can also be executed as scheduler task:
    See :ref:`Create and use Symfony commands in TYPO3 <t3coreapi:symfony-console-commands>`.

..  _creating-tasks-implementation:

Implementation of a custom scheduler task
=========================================

All scheduler task implementations **must** extend
:php:`\TYPO3\CMS\Scheduler\Task\AbstractTask`.

..  literalinclude:: _codesnippets/_MyTask.php.inc
    :language: php
    :caption: packages/my_extension/Classes/MyTask.php

A custom task implementation **must** override the method `execute(): bool`.
It is the main method that is called when a task is executed.
This method Should return `true` on successful execution, `false` on error.

..  note::
    There is no error handling by default, errors and failures are expected
    to be handled and logged by the client implementation.

Method `getAdditionalInformation()` **should** be implemented to provide
additional information in the schedulers backend module.

Scheduler task implementations that provide `additional fields <https://docs.typo3.org/permalink/typo3/cms-scheduler:additional-fields>`_
**should** implement additional methods, expecially `getTaskParameters()`.

..  _creating-tasks-registration:

Scheduler task registration and configuration
=============================================

..  deprecated:: 14.0
    Registering tasks and additional field providers via
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']` has
    been deprecated.

Custom scheduler tasks can be registered via TCA overrides, for example in
:file:`EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_my_task.php`

..  literalinclude:: _codesnippets/_tx_scheduler_my_task.php.inc
    :language: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_my_task.php

..  tip::

    Using the :php:`iconOverlay` option on task type registration, an icon
    overlay can be added, which is then displayed in the wizard. This can
    be useful for similar task types that use the same "base" `icon`, but
    still have to be differentiated.

..  include:: /_Includes/_ExtendingSchedulerTca.rst.txt

..  _additional-fields:

Providing additional fields for scheduler task
==============================================

..  deprecated:: 14.0
    Registering tasks and additional field providers via
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']` has
    been deprecated.

    The :php-short:`\TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface` and
    :php-short:`\TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider` have also
    been deprecated.

    Tasks in general and additional fields for tasks are registered via TCA
    instead.

    See also: `Migrating tasks with AdditionalFieldProviders to TCA registration <https://docs.typo3.org/permalink/typo3/cms-scheduler:additional-fields-migration>`_

Additional fields for scheduler tasks are handled via FormEngine and can be
configured via TCA.

If the task should provide additional fields for configuration options in
the backend module, you need to implement a second class, extending
:php-short:`\TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider`.

The task needs to be registered via TCA override:

..  literalinclude:: _codesnippets/_scheduler_my_task_type-additional.php.inc
    :language: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/scheduler_my_task_type.php

And implemented the following methods in your scheduler task if needed:

..  literalinclude:: _codesnippets/_MyTaskWithAdditionalFields.php.inc
    :language: php
    :caption: packages/my_extension/Classes/MyTask.php

..  note::
    Method `getTaskParameters()` should be implemented when
    `migrating tasks <https://docs.typo3.org/permalink/typo3/cms-scheduler:additional-fields-migration>`_

    For native TCA tasks, this method is typically no longer needed in custom
    tasks after the migration has been done, since field values are then stored
    directly in database columns.

..  seealso::
    There are additional examples in described in the
    `Changelog Feature: #107526 - Custom TCA types for scheduler tasks <https://docs.typo3.org/permalink/changelog:feature-107526-1747816234>`_.
