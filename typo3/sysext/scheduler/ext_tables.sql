CREATE TABLE tx_scheduler_task (
	serialized_task_object mediumblob,
	serialized_executions mediumblob,
	file_storage int(11) unsigned DEFAULT '0' NOT NULL,
	KEY index_nextexecution (nextexecution)
);
