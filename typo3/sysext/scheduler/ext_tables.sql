#
# Table structure for table 'tx_scheduler_task'
#
CREATE TABLE tx_scheduler_task (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	disable tinyint(4) unsigned DEFAULT '0' NOT NULL,
	nextexecution int(11) unsigned DEFAULT '0' NOT NULL,
	lastexecution_time int(11) unsigned DEFAULT '0' NOT NULL,
	lastexecution_failure text NOT NULL,
	lastexecution_context char(3) DEFAULT '' NOT NULL,
	serialized_task_object blob,
	serialized_executions blob,
	PRIMARY KEY (uid),
	KEY index_nextexecution (nextexecution)
);
