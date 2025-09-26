..  include:: /Includes.rst.txt
..  _deprecation-107550-1736193200:

===============================================================================
Deprecation: #107550 - Table Garbage Collection Task configuration via $GLOBALS
===============================================================================

See :issue:`107550`

Description
===========

The :php:`TableGarbageCollectionTask` has been migrated to use TYPO3's native
TCA-based task configuration system. As part of this migration, the previous
configuration method using :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']`
has been deprecated and will be removed in TYPO3 v15.

Impact
======

Using the old configuration method will trigger a PHP deprecation warning.
The functionality continues to work for now, with the deprecated configuration
being merged with the new TCA-based configuration automaticcaly.

Affected installations
======================

Any installation that configures custom tables for the :php:`TableGarbageCollectionTask`
using the :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']`
configuration.

The extension scanner will report any usage as weak match.

Migration
=========

Instead of configuring tables via :php:`$GLOBALS['TYPO3_CONF_VARS']`, tables
should now be configured in TCA using the :php:`taskOptions` configuration of the
corresponding record type within :file:`Configuration/TCA/Overrides`:

Before (deprecated):

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables'] = [
        'my_table' => [
            'dateField' => 'tstamp',
            'expirePeriod' => 90,
        ],
    ];

After (new method):

..  code-block:: php

    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        $GLOBALS['TCA']['tx_scheduler_task']['types'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['taskOptions']['tables'] = [
            'my_table' => [
                'dateField' => 'tstamp',
                'expirePeriod' => 90,
            ],
        ];
    }

It's also possible to modify the tables added by TYPO3, e.g. changing the
:php:`expirePeriod` of :sql:`sys_log`:

..  code-block:: php

    if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        $GLOBALS['TCA']['tx_scheduler_task']['types'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['taskOptions']['tables']['sys_log']['expirePeriod'] = 240;
    }

The new TCA-based configuration provides the same functionality while
integrating better with TYPO3's native scheduler task system and FormEngine.

..  index:: PHP-API, Scheduler, TCA, FullyScanned, ext:scheduler
