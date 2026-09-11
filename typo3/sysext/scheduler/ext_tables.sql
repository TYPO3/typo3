CREATE TABLE tx_scheduler_task (
	# @deprecated will be removed in TYPO3 v16 when the upgrade wizard is going to be removed
	serialized_task_object mediumblob,
	serialized_executions mediumblob,
	file_storage int(11) unsigned DEFAULT '0' NOT NULL,
	KEY index_nextexecution (nextexecution)
);
