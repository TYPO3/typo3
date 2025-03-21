# Define table and fields since it has no TCA
CREATE TABLE tx_scheduler_task (
	uid int(11) unsigned NOT NULL auto_increment,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	disable tinyint(4) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	description text,
	nextexecution int(11) unsigned DEFAULT '0' NOT NULL,
	lastexecution_time int(11) unsigned DEFAULT '0' NOT NULL,
	lastexecution_failure text,
	lastexecution_context varchar(3) DEFAULT '' NOT NULL,
	tasktype varchar(255) DEFAULT '' NOT NULL,
	parameters json,
	execution_details json,
	serialized_task_object mediumblob,
	serialized_executions mediumblob,
	task_group int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY index_nextexecution (nextexecution)
);
