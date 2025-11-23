..  include:: /Includes.rst.txt

..  _deprecation-107550-1736193200:

===============================================================================
Deprecation: #107550 - Table Garbage Collection Task configuration via $GLOBALS
===============================================================================

See :issue:`107550`

Description
===========

The :php:`\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask` has been
migrated to use TYPO3's native TCA-based task configuration system. As part of
this migration, the previous configuration method using

:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']`

has been deprecated and will be removed in TYPO3 v15.0.

Impact
======

Using the old configuration method will trigger a PHP deprecation warning.
The functionality continues to work for now, with the deprecated configuration
being merged with the new TCA-based configuration automatically.

Affected installations
======================

Any installation that configures custom tables for the
:php-short:`\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask` using the
deprecated :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']` configuration.

The extension scanner will report any usage as a **weak match**.

Migration
=========

Instead of configuring tables via :php:`$GLOBALS['TYPO3_CONF_VARS']`, tables
should now be configured in TCA using the `taskOptions` configuration of
the corresponding record type within :file:`Configuration/TCA/Overrides/`.

Before (deprecated):

..  code-block:: php
    :caption: ext_localconf.php

    use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']
        [TableGarbageCollectionTask::class]['options']['tables']
        ['tx_myextension_my_table'] = [
            'dateField' => 'tstamp',
            'expirePeriod' => 90,
        ];

After (new method):

..  code-block:: php
    :caption: Configuration/TCA/Overrides/scheduler_table_garbage_collection.php

    use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        $GLOBALS['TCA']['tx_scheduler_task']['types']
            [TableGarbageCollectionTask::class]['taskOptions']['tables']
            ['tx_myextension_my_table'] = [
                'dateField' => 'tstamp',
                'expirePeriod' => 90,
            ];
    }

It is also possible to modify the tables added by TYPO3, for example changing the
`expirePeriod` of table `sys_log`:

..  code-block:: php

    use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        $GLOBALS['TCA']['tx_scheduler_task']['types']
            [TableGarbageCollectionTask::class]['taskOptions']['tables']
            ['sys_log']['expirePeriod'] = 240;
    }

The new TCA-based configuration provides the same functionality while
integrating better with TYPO3's native scheduler task system and FormEngine.

..  index:: PHP-API, Scheduler, TCA, FullyScanned, ext:scheduler
