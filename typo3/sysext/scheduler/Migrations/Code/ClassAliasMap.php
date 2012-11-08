<?php
return array(
	'tx_scheduler_AdditionalFieldProvider' => 'TYPO3\\CMS\\Scheduler\\AdditionalFieldProviderInterface',
	'tx_scheduler_Module' => 'TYPO3\\CMS\\Scheduler\\Controller\\SchedulerModuleController',
	'tx_scheduler_CronCmd' => 'TYPO3\\CMS\\Scheduler\\CronCommand\\CronCommand',
	'tx_scheduler_CronCmd_Normalize' => 'TYPO3\\CMS\\Scheduler\\CronCommand\\NormalizeCommand',
	'tx_scheduler_SleepTask' => 'TYPO3\\CMS\\Scheduler\\Example\\SleepTask',
	'tx_scheduler_SleepTask_AdditionalFieldProvider' => 'TYPO3\\CMS\\Scheduler\\Example\\SleepTaskAdditionalFieldProvider',
	'tx_scheduler_Execution' => 'TYPO3\\CMS\\Scheduler\\Execution',
	'tx_scheduler_FailedExecutionException' => 'TYPO3\\CMS\\Scheduler\\FailedExecutionException',
	'tx_scheduler_ProgressProvider' => 'TYPO3\\CMS\\Scheduler\\ProgressProviderInterface',
	'tx_scheduler' => 'TYPO3\\CMS\\Scheduler\\Scheduler',
	'tx_scheduler_Task' => 'TYPO3\\CMS\\Scheduler\\Task\\AbstractTask',
	'tx_scheduler_CachingFrameworkGarbageCollection_AdditionalFieldProvider' => 'TYPO3\\CMS\\Scheduler\\Task\\CachingFrameworkGarbageCollectionAdditionalFieldProvider',
	'tx_scheduler_CachingFrameworkGarbageCollection' => 'TYPO3\\CMS\\Scheduler\\Task\\CachingFrameworkGarbageCollectionTask',
	'tx_scheduler_FileIndexing' => 'TYPO3\\CMS\\Scheduler\\Task\\FileIndexingTask',
	'tx_scheduler_RecyclerGarbageCollection_AdditionalFieldProvider' => 'TYPO3\\CMS\\Scheduler\\Task\\RecyclerGarbageCollectionAdditionalFieldProvider',
	'tx_scheduler_RecyclerGarbageCollection' => 'TYPO3\\CMS\\Scheduler\\Task\\RecyclerGarbageCollectionTask',
	'tx_scheduler_TableGarbageCollection_AdditionalFieldProvider' => 'TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionAdditionalFieldProvider',
	'tx_scheduler_TableGarbageCollection' => 'TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask',
);
?>