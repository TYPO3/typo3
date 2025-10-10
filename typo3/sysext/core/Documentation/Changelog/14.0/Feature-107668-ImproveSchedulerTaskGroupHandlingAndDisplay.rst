.. include:: /Includes.rst.txt

.. _feature-107668-1760123722:

=====================================================================
Feature: #107668 - Improve Scheduler task group handling and display
=====================================================================

See :issue:`107668`

Description
===========

The scheduler module has been enhanced with improved visual organization and
quick editing capabilities for task groups. Task groups can now be assigned
custom colors to improve visual distinction, and the group name has been
made directly editable from the module view.

A new :sql:`color` field has been added to the :sql:`tx_scheduler_task_group`
table, allowing administrators to assign hex color values to each task group.

UI Improvements
===============

The scheduler module now provides several enhancements for task groups:

**Color coding**
   Task groups can be assigned a color that is displayed as a left border
   on the group panel, similar to the page module label, making it easy
   to visually distinguish between different groups at a glance.

**Quick edit link**
   The group name is now a clickable link that opens the group record for
   editing, providing direct access to the group name, color, and description
   fields without navigating through the list module.

**Description display**
   Group descriptions are now displayed in the scheduler module directly below
   the group name, providing additional context about the purpose of each group.

**Bold group names**
   Group names are now displayed in bold for better visual hierarchy and
   readability in the scheduler module interface.

Implementation Details
======================

The color field is implemented as a TCA field of type :php:`'color'` with
the following characteristics:

*  Stores standard hex color values (e.g., :php:`#FF8700`)
*  Includes a value picker with 11 predefined colors aligned with the TYPO3
   brand palette

Predefined color options:

*  TYPO3 Orange (#FF8700)
*  White (#ffffff)
*  Gray (#808080)
*  Black (#000000)
*  Blue (#2671d9)
*  Purple (#5e4db2)
*  Teal (#2da8d2)
*  Green (#3cc38c)
*  Magenta (#c6398f)
*  Yellow (#ffbf00)
*  Red (#d13a2e)

Those options can be customized by manipulating the
:php:`$GLOBAS['TCA']['tx_scheduler_task_group']['columns']['color']['config']['valuePicker']['items']`
array in your TCA overrides file.

.. code-block:: php

    $GLOBALS['TCA']['tx_scheduler_task_group']['columns']['color']['config']['valuePicker']['items'][] = [
        'label' => 'My Color',
        'value' => '#ABCDEF'
    ];

Impact
======

These enhancements improve the usability of the scheduler module, particularly
for installations with many task groups. The improvements allow administrators
to:

*  Quickly identify related task groups through visual color coding
*  Edit group properties directly from the scheduler module
*  Organize tasks by category, priority, or purpose
*  See group descriptions without opening the group record
*  Improve visual navigation in large scheduler configurations

The feature is fully backward compatible - existing task groups without colors
continue to work as before, displaying without a colored border.

.. index:: Backend, ext:scheduler, TCA
