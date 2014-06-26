#
# Table structure for table 'sys_workspace'
#
CREATE TABLE sys_workspace (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(1) DEFAULT '0' NOT NULL,
	title varchar(30) DEFAULT '' NOT NULL,
	description varchar(255) DEFAULT '' NOT NULL,
	adminusers varchar(4000) DEFAULT '' NOT NULL,
	members varchar(4000) DEFAULT '' NOT NULL,
	reviewers varchar(4000) DEFAULT '' NOT NULL,
	db_mountpoints varchar(255) DEFAULT '' NOT NULL,
	file_mountpoints varchar(255) DEFAULT '' NOT NULL,
	publish_time int(11) DEFAULT '0' NOT NULL,
	unpublish_time int(11) DEFAULT '0' NOT NULL,
	freeze tinyint(3) DEFAULT '0' NOT NULL,
	live_edit tinyint(3) DEFAULT '0' NOT NULL,
	vtypes tinyint(3) DEFAULT '0' NOT NULL,
	swap_modes tinyint(3) DEFAULT '0' NOT NULL,
	publish_access tinyint(3) DEFAULT '0' NOT NULL,
	custom_stages int(11) DEFAULT '0' NOT NULL,
	stagechg_notification tinyint(3) DEFAULT '0' NOT NULL,
	edit_notification_mode tinyint(3) DEFAULT '0' NOT NULL,
	edit_notification_defaults varchar(255) DEFAULT '' NOT NULL,
	edit_notification_preselection tinyint(3) DEFAULT '3' NOT NULL,
	edit_allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,
	publish_notification_mode tinyint(3) DEFAULT '0' NOT NULL,
	publish_notification_defaults varchar(255) DEFAULT '' NOT NULL,
	publish_notification_preselection tinyint(3) DEFAULT '3' NOT NULL,
	publish_allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,
	execute_notification_mode tinyint(3) DEFAULT '0' NOT NULL,
	execute_notification_defaults varchar(255) DEFAULT '' NOT NULL,
	execute_notification_preselection tinyint(3) DEFAULT '3' NOT NULL,
	execute_allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'sys_workspace_stage'
#
CREATE TABLE sys_workspace_stage (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(1) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	title varchar(30) DEFAULT '' NOT NULL,
	responsible_persons varchar(255) DEFAULT '' NOT NULL,
	default_mailcomment text,
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable tinytext NOT NULL,
	notification_mode tinyint(3) DEFAULT '0' NOT NULL,
	notification_defaults varchar(255) DEFAULT '' NOT NULL,
	allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,
	notification_preselection tinyint(3) DEFAULT '8' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
