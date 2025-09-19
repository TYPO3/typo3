.. include:: /Includes.rst.txt

.. _feature-107488-1758106735:

===========================================================
Feature: #107488 - Extensible scheduler task timing options
===========================================================

See :issue:`107488`

Description
===========

The scheduler task timing configuration has been migrated to the single TCA
:php:`execution_details` field, which is of type `json`. This field has
now been enhanced to allow extensions to customize the corresponding
timing-related fields, particularly the frequency field with custom cron
expressions. This is achieved through the new :php:`overrideFieldTca`
option, available in the TCA field configuration.

Previously, frequency options were only configurable through the global
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions']`
array, which has been moved to the TCA configuration to provide better
extensibility and consistency with TYPO3's configuration patterns, see
:ref:`breaking-107488-1758106735`.


Enhanced Customization Options
==============================

**Field-level Configuration**
Extensions can now override any timing-related field configuration using the
:php:`overrideFieldTca` mechanism in the :php:`execution_details` field.

Available fields:

* **Frequency field**: :php:`frequency`
* **Running type field**: :php:`runningType`
* **Parallel execution settings**: :php:`multiple`
* **Start/End date fields**: :php:`start` and :php:`end`

Example Usage
=============

Extensions can now add custom frequency options by creating a TCA override file:

..  code-block:: php

    // EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_task.php

    $GLOBALS['TCA']['tx_scheduler_task']['columns']['execution_details']['config']['overrideFieldTca']['frequency']['config']['valuePicker']['items'][] = [
        'value' => '0 2 * * *',
        'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:daily_2am',
    ];

Extensions can now add a description:

..  code-block:: php

    // EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_task.php

    $GLOBALS['TCA']['tx_scheduler_task']['columns']['execution_details']['config']['overrideFieldTca']['multiple'] = [
        'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:multiple.description',
    ];


Related Changes
===============

See :ref:`breaking-107488-1758106735` for information about the breaking
change regarding the removal of the global `frequencyOptions` configuration.

Impact
======

This enhancement provides a more flexible and extensible way to configure
scheduler task timing options, allowing extensions to seamlessly integrate
custom timing configurations while maintaining consistency with TYPO3's
configuration patterns.

.. index:: Backend, TCA, ext:scheduler
