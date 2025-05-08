CREATE TABLE tx_scheduler_task (
	serialized_task_object mediumblob,
	serialized_executions mediumblob,
	KEY index_nextexecution (nextexecution)
);
