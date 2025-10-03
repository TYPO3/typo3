<?php

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Scheduler\Task\IpAnonymizationTask;

if ((new Typo3Version())->getMajorVersion() < 14) {
    // TODO: Remove once TYPO3 13 support is dropped
    // TYPO3 14 configuration can be found in Configuration/TCA/Overrides/tx_scheduler_ip_anonymization.php
    $garbageCollectionTables =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][IpAnonymizationTask::class]['options']['tables'];

    $garbageCollectionTables = array_replace($garbageCollectionTables ?? [], [
        'tx_myextension_my_table' => [
            'dateField' => 'tstamp',
            'ipField' => 'private_ip',
        ],
    ]);
}
