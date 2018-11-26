#
# Table structure for table 'tx_scheduler_task'
#
CREATE TABLE tx_scheduler_task (
	uid int(11) unsigned NOT NULL auto_increment,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	disable tinyint(4) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	description text,
	nextexecution int(11) unsigned DEFAULT '0' NOT NULL,
	lastexecution_time int(11) unsigned DEFAULT '0' NOT NULL,
	lastexecution_failure text,
	lastexecution_context char(3) DEFAULT '' NOT NULL,
	serialized_task_object mediumblob,
	serialized_executions mediumblob,
	task_group int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY index_nextexecution (nextexecution)
);

#
# Table structure for table 'tx_scheduler_task_group'
#
CREATE TABLE tx_scheduler_task_group (
	groupName varchar(80) DEFAULT '' NOT NULL,
	description text
);
