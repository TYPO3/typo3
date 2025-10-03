<?php

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    $garbageCollectionTables =& $GLOBALS['TCA']['tx_scheduler_task']['types'][TableGarbageCollectionTask::class]['taskOptions']['tables'];

    $garbageCollectionTables = array_replace($garbageCollectionTables ?? [], [
        'tx_myextension_my_table' => [
            'dateField' => 'tstamp',
            'expirePeriod' => 180,
        ],
        'tx_myextension_my_other_table' => [
            'expireField' => 'expire',
        ],
    ]);
}
