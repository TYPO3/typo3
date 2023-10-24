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
	file_mountpoints text,
	stagechg_notification tinyint(3) DEFAULT '0' NOT NULL,
);


#
# Table structure for table 'sys_workspace_stage'
#
CREATE TABLE sys_workspace_stage (
	title varchar(30) DEFAULT '' NOT NULL,
);
