#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
  workspace_perms tinyint(3) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'sys_preview'
#
CREATE TABLE sys_preview (
  keyword varchar(32) DEFAULT '' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  endtime int(11) DEFAULT '0' NOT NULL,
  config text,
  PRIMARY KEY (keyword)
);

#
# Table structure for table 'sys_workspace'
#
CREATE TABLE sys_workspace (
	title varchar(30) DEFAULT '' NOT NULL,
	adminusers varchar(4000) DEFAULT '' NOT NULL,
	members varchar(4000) DEFAULT '' NOT NULL,
	db_mountpoints text,
	file_mountpoints text,
	freeze tinyint(3) DEFAULT '0' NOT NULL,
	live_edit tinyint(3) DEFAULT '0' NOT NULL,
	publish_access tinyint(3) DEFAULT '0' NOT NULL,
	previewlink_lifetime int(11) DEFAULT '0' NOT NULL,
	custom_stages int(11) DEFAULT '0' NOT NULL,
	stagechg_notification tinyint(3) DEFAULT '0' NOT NULL,
	edit_notification_defaults varchar(255) DEFAULT '' NOT NULL,
	edit_notification_preselection tinyint(3) DEFAULT '3' NOT NULL,
	edit_allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,
	publish_notification_defaults varchar(255) DEFAULT '' NOT NULL,
	publish_notification_preselection tinyint(3) DEFAULT '3' NOT NULL,
	publish_allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,
	execute_notification_defaults varchar(255) DEFAULT '' NOT NULL,
	execute_notification_preselection tinyint(3) DEFAULT '3' NOT NULL,
	execute_allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL
);


#
# Table structure for table 'sys_workspace_stage'
#
CREATE TABLE sys_workspace_stage (
	title varchar(30) DEFAULT '' NOT NULL,
	responsible_persons varchar(255) DEFAULT '' NOT NULL,
	default_mailcomment text,
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable varchar(255) DEFAULT '' NOT NULL,
	notification_defaults varchar(255) DEFAULT '' NOT NULL,
	allow_notificaton_settings tinyint(3) DEFAULT '0' NOT NULL,
	notification_preselection tinyint(3) DEFAULT '8' NOT NULL
);
