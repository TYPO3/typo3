:navigation-title: Grouping

..  include:: /Includes.rst.txt
..  _grouping-tasks:

=======================================================
Grouping tasks together in the Scheduler backend module
=======================================================

In case of a high number of different tasks, it may be useful to visually
group similar tasks together:

..  figure:: /Images/GroupedTasks.png
    :alt: Screenshot of the TYPO3 backend module scheduler with buttons regarding groups highlighted

    Use button :guilabel:`New group` (1) to create a group, click on the title (2) to edit the group

You can sort and disable groups (3).

Unused groups are displayed at the bottom of the page. Only unused groups can
be deleted (4).

When editing a group you can change its title and color.

..  figure:: /Images/GroupEdit.png
    :alt: Screenshot of the task group form

Color options can be customized by manipulating the
:php:`$GLOBAS['TCA']['tx_scheduler_task_group']['columns']['color']['config']['valuePicker']['items']`
array in your TCA overrides file.

..  code-block:: php
    :caption: packages/my-sitepackage/Configuration/Overrides/tx_scheduler_task_group.php

    $GLOBALS['TCA']['tx_scheduler_task_group']['columns']['color']['config']['valuePicker']['items'][] = [
        'label' => 'My Color',
        'value' => '#ABCDEF'
    ];

..  _grouping-tasks-edit:

Editing task groups
===================

Scheduler task groups can be created, edited and deleted from the module
:guilabel:`System > Scheduler`.

Technically they are records stored on the root page (pid=0). They can also be
created, edited and sorted with module :guilabel:`Web > List`.

It is also possible to create a new task group from within the edit task form by
clicking on the `+` icon next to the task group select box.

..  _grouping-tasks-disable:

Disabling task groups
=====================

You can use button :guilabel:`Disable group` to disable all tasks in a group
at once.

..  figure:: /Images/GroupDisabled.png
    :alt: Screenshot a disabled task group, all tasks are marked as disabled by group

    Use button :guilabel:`Enable group` to enable all tasks that had not been manually disabled.

Tasks in a disabled group, just like disabled tasks in general are not executed
when the scheduler is called by the cron job. They can, however, be executed
manually by clicking the :guilabel:`Run task` button.
