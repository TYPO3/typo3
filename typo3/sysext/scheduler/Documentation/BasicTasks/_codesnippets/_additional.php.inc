<?php

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

if ((new Typo3Version())->getMajorVersion() < 14) {
    // TODO: Remove once TYPO3 13 support is dropped
    // TYPO3 14 configuration can be found in Configuration/TCA/Overrides/tx_scheduler_garbage_collection.php
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_myextension_errorlog'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => '180',
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_myextension_uniqalias'] = [
        'expireField' => 'expire',
    ];
}
