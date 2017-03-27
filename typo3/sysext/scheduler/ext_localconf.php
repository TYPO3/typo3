<?php
defined('TYPO3_MODE') or die();

// Register the Scheduler as a possible key for CLI calls
// Using cliKeys is deprecated as of TYPO3 v8 and will be removed in TYPO3 v9, use Configuration/Commands.php instead
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['scheduler'] = [
    function ($input, $output) {
        $app = new \Symfony\Component\Console\Application('TYPO3 Scheduler', TYPO3_version);
        $app->add(new \TYPO3\CMS\Scheduler\Command\SchedulerCommand('scheduler'));
        $app->setDefaultCommand('scheduler');
        $app->run($input, $output);
    }
];

// Get the extensions's configuration
$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scheduler'], ['allowed_classes' => false]);
// If sample tasks should be shown,
// register information for the test and sleep tasks
if (!empty($extConf['showSampleTasks'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Example\TestTask::class] = [
        'extension' => 'scheduler',
        'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:testTask.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:testTask.description',
        'additionalFields' => \TYPO3\CMS\Scheduler\Example\TestTaskAdditionalFieldProvider::class
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Example\SleepTask::class] = [
        'extension' => 'scheduler',
        'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:sleepTask.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:sleepTask.description',
        'additionalFields' => \TYPO3\CMS\Scheduler\Example\SleepTaskAdditionalFieldProvider::class
    ];
}

// Add caching framework garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:cachingFrameworkGarbageCollection.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:cachingFrameworkGarbageCollection.description',
    'additionalFields' => \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionAdditionalFieldProvider::class
];

// Add task to index file in a storage
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\FileStorageIndexingTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageIndexing.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageIndexing.description',
    'additionalFields' => \TYPO3\CMS\Scheduler\Task\FileStorageIndexingAdditionalFieldProvider::class
];

// Add task for extracting metadata from files in a storage
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\FileStorageExtractionTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageExtraction.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageExtraction.description',
    'additionalFields' => \TYPO3\CMS\Scheduler\Task\FileStorageExtractionAdditionalFieldProvider::class

];

// Add recycler directory cleanup task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\RecyclerGarbageCollectionTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:recyclerGarbageCollection.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:recyclerGarbageCollection.description',
    'additionalFields' => \TYPO3\CMS\Scheduler\Task\RecyclerGarbageCollectionAdditionalFieldProvider::class
];

// Save any previous option array for table garbage collection task
// to temporary variable so it can be pre-populated by other
// extensions and LocalConfiguration/AdditionalConfiguration
$garbageCollectionTaskOptions = [];
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options'])) {
    $garbageCollectionTaskOptions = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options'];
}
// Initialize tables sub-array if not set already
if (!is_array($garbageCollectionTaskOptions['tables'])) {
    $garbageCollectionTaskOptions['tables'] = [];
}
// Add table garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tableGarbageCollection.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tableGarbageCollection.description',
    'additionalFields' => \TYPO3\CMS\Scheduler\Task\TableGarbageCollectionAdditionalFieldProvider::class,
    'options' => $garbageCollectionTaskOptions
];
unset($garbageCollectionTaskOptions);

// Register sys_log and sys_history table in table garbage collection task
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['sys_log'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['sys_log'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 180
    ];
}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['sys_history'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['sys_history'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 30
    ];
}

// Add task for optimizing database tables
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\OptimizeDatabaseTableTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:optimizeDatabaseTable.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:optimizeDatabaseTable.description',
    'additionalFields' => \TYPO3\CMS\Scheduler\Task\OptimizeDatabaseTableAdditionalFieldProvider::class

];
