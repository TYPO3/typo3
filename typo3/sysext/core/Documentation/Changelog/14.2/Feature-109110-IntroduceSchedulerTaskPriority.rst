..  include:: /Includes.rst.txt

..  _feature-109110-1742558400:

====================================================
Feature: #109110 - Introduce scheduler task priority
====================================================

See :issue:`109110`

Description
===========

A new :sql:`priority` column has been added to the
:sql:`tx_scheduler_task` table, allowing administrators to control the
execution order of scheduler tasks. Three levels are available:

*   **High** (150)
*   **Regular** (100, default)
*   **Low** (50)

The scheduler now selects the next executable task ordered by
:sql:`priority DESC` first, using :sql:`nextexecution ASC` as a secondary
tiebreaker. This means a high-priority task is always
executed before a lower-priority task, regardless of how long the
lower-priority task has been waiting.

The priority field is exposed as a select field in the **Timing** tab of
the task editing form in all registered task types. The priority of each
task is also visible in the scheduler backend module list view.

Extending priority levels
=========================

Extensions can add custom priority levels by extending the TCA of
:sql:`tx_scheduler_task`. The :sql:`priority` field is a plain integer
column, so any positive integer value is valid. The scheduler module
automatically resolves the label of any registered TCA item, so that custom
values are displayed correctly in the list view. The TCA item's
:php:`label` key must point to a valid language label. If no matching item
is found, the raw integer is shown.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_task.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addTcaSelectItem(
        'tx_scheduler_task',
        'priority',
        [
            'label' => 'LLL:my_extension.messages:priority.critical',
            'value' => 200,
        ],
        150,
        'after',
    );

Choose integer values that fit naturally into the existing scale (50 /
100 / 150). Values above 150 are executed before **High**, values below
50 after **Low**.

Impact
======

Administrators can now assign a priority to each scheduler task. Tasks
with **High** priority are picked up before **Regular** tasks, and
**Regular** before **Low** tasks. If multiple tasks share the same
priority, the longest-overdue task is still selected first, preserving
the previous behavior as a tiebreaker.

Existing tasks receive the default priority **Regular** (100)
automatically via the schema update — no data migration is required.

..  index:: Backend, Database, TCA, ext:scheduler
