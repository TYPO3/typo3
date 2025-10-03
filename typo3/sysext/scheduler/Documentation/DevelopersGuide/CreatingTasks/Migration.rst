:navigation-title: Migration

..  include:: /Includes.rst.txt

..  _task-migration:

=====================================================
Migration to the TCA registration for scheduler tasks
=====================================================

..  deprecated:: 14.0
    Registering tasks and additional field providers via
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']` has
    been deprecated.

    The :php-short:`\TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface` and
    :php-short:`\TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider` have also
    been deprecated.

..  contents:: Table of contents

    Tasks in general and additional fields for tasks are registered via TCA
    instead.
..  _additional-fields-migration:

Migrating tasks with AdditionalFieldProviders to TCA registration
=================================================================

Scheduler tasks should now be registered as native task types using TCA.
This provides a more integrated and maintainable approach to task configuration.

..  _additional-fields-migration-steps:

Migration steps:
----------------

1.  Remove the registration from :file:`ext_localconf.php`
2.  Create a TCA override file in :file:`Configuration/TCA/Overrides/scheduler_my_task_type.php`
3.  Update your task class to implement the new parameter methods
4.  Remove the :php:`AdditionalFieldProvider` class if it exists

..  note::
    The new TCA-based approach automatically migrates existing task data.
    When upgrading, existing task configurations are preserved through the
    :php:`getTaskParameters()` and :php:`setTaskParameters()` methods.

..  _additional-fields-migration-example:

Example migration: Scheduler task with additional fields suppporting TYPO3 13 and 14
------------------------------------------------------------------------------------

Remove the registration from :file:`ext_localconf.php` once TYPO3 13 support is
dropped:

..  literalinclude:: _codesnippets/_ext_localconf_deprecated.php.inc
    :language: php
    :caption: packages/my_extension/ext_localconf.php

And also remove the :php:`MyTaskAdditionalFieldProvider` class once
TYPO3 13 support is dropped.

Create a TCA override file in :file:`Configuration/TCA/Overrides/scheduler_my_task_type.php`:

..  literalinclude:: _codesnippets/_scheduler_my_task_type-additional.php.inc
    :language: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/scheduler_my_task_type.php

Update your (existing) task class to implement the new methods:

..  literalinclude:: _codesnippets/_MyTaskWithAdditionalFieldsMigration.php.inc
    :language: php
    :caption: packages/my_extension/Classes/MyTask.php

The new TCA-based approach uses three key methods for parameter handling:

**getTaskParameters(): array**
    This method is already implemented in ``AbstractTask`` to handle task class
    properties automatically, but can be overridden in task classes for custom
    behavior.

    The method is primarily used:

    * For migration from old serialized task format to new TCA structure
    * For non-native (deprecated) task types to store their values in the legacy ``parameters`` field

    For native TCA tasks, this method is typically no longer needed in custom
    tasks after the migration has been done, since field values are then stored
    directly in database columns.

**setTaskParameters(array $parameters): void**
    Sets field values from an associative array. This method handles:

    * Migration from old AdditionalFieldProvider field names to new TCA field names
    * Loading saved task configurations when editing or executing tasks
    * Parameter mapping during task creation and updates
    * The method should always be implemented, especially for native tasks

    The migration pattern is: :php:`$this->myField = $parameters['oldName'] ?? $parameters['new_tca_field_name'] ?? '';`

**validateTaskParameters(array $parameters): bool**
    *Optional method.* Only implement this for validation that cannot be handled by FormEngine.

    * Basic validation (required, trim, etc.) should be done via TCA configuration (``required`` property and ``eval`` options)
    * Use this method for complex business logic validation (e.g., email format validation, external API checks)
    * Return ``false`` and add FlashMessage for validation errors
    * FormEngine automatically handles standard TCA validation rules

For a complete working example, see :php:`\TYPO3\CMS\Reports\Task\SystemStatusUpdateTask`
and its corresponding TCA configuration in
:file:`EXT:reports/Configuration/TCA/Overrides/scheduler_system_status_update_task.php`.
