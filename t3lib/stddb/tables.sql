# MySQL dump 6.4
#
# Host: localhost    Database: t3_testsite
#--------------------------------------------------------
# Server version	3.22.27
#
# TYPO3 CVS ID: $Id$

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  title varchar(20) DEFAULT '' NOT NULL,
  non_exclude_fields blob NOT NULL,
  explicit_allowdeny blob NOT NULL,
  allowed_languages tinyblob NOT NULL,
  custom_options blob NOT NULL,
  db_mountpoints varchar(40) DEFAULT '' NOT NULL,
  pagetypes_select tinyblob NOT NULL,
  tables_select blob NOT NULL,
  tables_modify blob NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  groupMods tinyblob NOT NULL,
  file_mountpoints varchar(40) DEFAULT '' NOT NULL,
  hidden tinyint(3) unsigned DEFAULT '0' NOT NULL,
  inc_access_lists tinyint(3) unsigned DEFAULT '0' NOT NULL,
  description text NOT NULL,
  lockToDomain varchar(50) DEFAULT '' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  TSconfig blob NOT NULL,
  subgroup tinyblob NOT NULL,
  hide_in_lists tinyint(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'be_sessions'
#
CREATE TABLE be_sessions (
  ses_id varchar(32) DEFAULT '' NOT NULL,
  ses_name varchar(32) DEFAULT '' NOT NULL,
  ses_iplock varchar(15) DEFAULT '' NOT NULL,
  ses_hashlock int(11) DEFAULT '0' NOT NULL,
  ses_userid int(11) unsigned DEFAULT '0' NOT NULL,
  ses_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  ses_data mediumblob NOT NULL,
  PRIMARY KEY (ses_id,ses_name)
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  username varchar(20) DEFAULT '' NOT NULL,
  password varchar(40) DEFAULT '' NOT NULL,
  admin tinyint(4) unsigned DEFAULT '0' NOT NULL,
  usergroup tinyblob NOT NULL,
  disable tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  lang char(2) DEFAULT '' NOT NULL,
  email varchar(80) DEFAULT '' NOT NULL,
  db_mountpoints varchar(40) DEFAULT '' NOT NULL,
  options tinyint(4) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  realName varchar(80) DEFAULT '' NOT NULL,
  userMods tinyblob NOT NULL,
  allowed_languages tinyblob NOT NULL,
  uc blob NOT NULL,
  file_mountpoints varchar(40) DEFAULT '' NOT NULL,
  fileoper_perms tinyint(4) DEFAULT '0' NOT NULL,
  lockToDomain varchar(50) DEFAULT '' NOT NULL,
  disableIPlock tinyint(3) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  TSconfig blob NOT NULL,
  lastlogin int(10) unsigned DEFAULT '0' NOT NULL,
  createdByAction int(11) DEFAULT '0' NOT NULL,
  usergroup_cached_list tinytext NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY username (username)
);

#
# Table structure for table 'cache_hash'
#
CREATE TABLE cache_hash (
  hash varchar(32) DEFAULT '' NOT NULL,
  content mediumblob NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  ident varchar(20) DEFAULT '' NOT NULL,
  PRIMARY KEY (hash)
);

#
# Table structure for table 'cache_imagesizes'
#
CREATE TABLE cache_imagesizes (
  md5hash varchar(32) DEFAULT '' NOT NULL,
  md5filename varchar(32) DEFAULT '' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  filename tinytext NOT NULL,
  imagewidth mediumint(11) unsigned DEFAULT '0' NOT NULL,
  imageheight mediumint(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (md5filename)
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
  uid int(11) DEFAULT '0' NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  t3ver_oid int(11) unsigned DEFAULT '0' NOT NULL,
  t3ver_id int(11) unsigned DEFAULT '0' NOT NULL,
  t3ver_label varchar(30) DEFAULT '' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  perms_userid int(11) unsigned DEFAULT '0' NOT NULL,
  perms_groupid int(11) unsigned DEFAULT '0' NOT NULL,
  perms_user tinyint(4) unsigned DEFAULT '0' NOT NULL,
  perms_group tinyint(4) unsigned DEFAULT '0' NOT NULL,
  perms_everybody tinyint(4) unsigned DEFAULT '0' NOT NULL,
  editlock tinyint(4) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  doktype tinyint(3) unsigned DEFAULT '0' NOT NULL,
  TSconfig blob NOT NULL,
  storage_pid int(11) DEFAULT '0' NOT NULL,
  is_siteroot tinyint(4) DEFAULT '0' NOT NULL,
  php_tree_stop tinyint(4) DEFAULT '0' NOT NULL,
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY t3ver_oid (t3ver_oid),
  KEY parent (pid),
);

#
# Table structure for table 'sys_be_shortcuts'
#
CREATE TABLE sys_be_shortcuts (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  userid int(11) unsigned DEFAULT '0' NOT NULL,
  module_name tinytext NOT NULL,
  url text NOT NULL,
  description tinytext NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  sc_group tinyint(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY event (userid)
);

#
# Table structure for table 'sys_filemounts'
#
CREATE TABLE sys_filemounts (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  title varchar(30) DEFAULT '' NOT NULL,
  path varchar(120) DEFAULT '' NOT NULL,
  base tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(3) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'sys_history'
#
CREATE TABLE sys_history (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  sys_log_uid int(11) DEFAULT '0' NOT NULL,
  history_data mediumblob NOT NULL,
  fieldlist blob NOT NULL,
  recuid int(11) DEFAULT '0' NOT NULL,
  tablename varchar(40) DEFAULT '' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  history_files mediumblob NOT NULL,
  snapshot tinyint(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY recordident (tablename,recuid),
  KEY sys_log_uid (sys_log_uid)
);

#
# Table structure for table 'sys_lockedrecords'
#
CREATE TABLE sys_lockedrecords (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  userid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  record_table varchar(40) DEFAULT '' NOT NULL,
  record_uid int(11) DEFAULT '0' NOT NULL,
  record_pid int(11) DEFAULT '0' NOT NULL,
  username varchar(20) DEFAULT '' NOT NULL,
  PRIMARY KEY (uid),
  KEY event (userid,tstamp)
);

#
# Table structure for table 'sys_log'
#
CREATE TABLE sys_log (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  userid int(11) unsigned DEFAULT '0' NOT NULL,
  action tinyint(4) unsigned DEFAULT '0' NOT NULL,
  recuid int(11) unsigned DEFAULT '0' NOT NULL,
  tablename varchar(40) DEFAULT '' NOT NULL,
  recpid int(11) DEFAULT '0' NOT NULL,
  error tinyint(4) unsigned DEFAULT '0' NOT NULL,
  details tinytext NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  type tinyint(3) unsigned DEFAULT '0' NOT NULL,
  details_nr tinyint(3) unsigned DEFAULT '0' NOT NULL,
  IP varchar(15) DEFAULT '' NOT NULL,
  log_data tinyblob NOT NULL,
  event_pid int(11) DEFAULT '-1' NOT NULL,
  NEWid varchar(20) DEFAULT '' NOT NULL,
  PRIMARY KEY (uid),
  KEY event (userid,event_pid)
);

#
# Table structure for table 'sys_language'
#
CREATE TABLE sys_language (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  title varchar(80) DEFAULT '' NOT NULL,
  flag varchar(20) DEFAULT '' NOT NULL,
  static_lang_isocode int(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

