#
# Table structure for table 'sys_note'
#
CREATE TABLE sys_note (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser int(11) unsigned DEFAULT '0' NOT NULL,
  author varchar(80) DEFAULT '' NOT NULL,
  email varchar(80) DEFAULT '' NOT NULL,
  subject tinytext NOT NULL,
  message text NOT NULL,
  personal tinyint(3) unsigned DEFAULT '0' NOT NULL,
  category tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);
