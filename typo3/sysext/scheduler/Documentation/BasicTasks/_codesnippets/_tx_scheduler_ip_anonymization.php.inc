<?php

use TYPO3\CMS\Scheduler\Task\IpAnonymizationTask;

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    $garbageCollectionTables =& $GLOBALS['TCA']['tx_scheduler_task']['types'][IpAnonymizationTask::class]['taskOptions']['tables'];

    $garbageCollectionTables = array_replace($garbageCollectionTables ?? [], [
        'tx_myextension_my_table' => [
            'dateField' => 'tstamp',
            'ipField' => 'private_ip',
        ],
    ]);
}
