.. include:: /Includes.rst.txt

.. _breaking-107488-1758106735:

============================================================
Breaking: #107488 - Scheduler frequency options moved to TCA
============================================================

See :issue:`107488`

Description
===========

The global configuration array :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions']`
used to define frequency options for scheduler tasks has been removed. The
frequency options are now configured directly in the TCA configuration using
the :php:`overrideFieldTca` mechanism on the :php:`tx_scheduler_task.execution_details`
field.

This change improves consistency with TYPO3's configuration patterns and
provides better extensibility for scheduler task timing options.

Impact
======

Extensions that previously added custom frequency options through the global
:php:`frequencyOptions` array will no longer see their custom options in the
scheduler task frequency field.

Code that relied on reading the global :php:`frequencyOptions` configuration may
no longer work as expected.

Affected Installations
======================

All installations that have extensions providing custom scheduler frequency
options through the global configuration array are affected.

Migration
=========

Extensions should migrate their frequency options from the global configuration
to TCA overrides.

**Before (no longer working):**

..  code-block:: php

    // EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions']['0 2 * * *'] =
        'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:daily_2am';

**After (recommended approach):**

..  code-block:: php

    // EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_task.php

    $GLOBALS['TCA']['tx_scheduler_task']['columns']['execution_details']['config']['overrideFieldTca']['frequency']['config']['valuePicker']['items'][] = [
        'value' => '0 2 * * *',
        'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:daily_2am',
    ];

**Migration for multiple options:**

..  code-block:: php

    // EXT:my_extension/Configuration/TCA/Overrides/tx_scheduler_task.php

    $customFrequencyOptions = [
        [
            'value' => '0 2 * * *',
            'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:daily_2am',
        ],
        [
            'value' => '0 */6 * * *',
            'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:every_6_hours',
        ],
        [
            'value' => '0 0 1 * *',
            'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:monthly_first',
        ],
    ];

    $GLOBALS['TCA']['tx_scheduler_task']['columns']['execution_details']['config']['overrideFieldTca']['frequency']['config']['valuePicker']['items'] = array_merge(
        $GLOBALS['TCA']['tx_scheduler_task']['columns']['execution_details']['config']['overrideFieldTca']['frequency']['config']['valuePicker']['items'] ?? [],
        $customFrequencyOptions
    );

Related Features
=================

See :ref:`feature-107488-1758106735` for information about the new extensible
timing options functionality.

.. index:: Backend, TCA, NotScanned, ext:scheduler
