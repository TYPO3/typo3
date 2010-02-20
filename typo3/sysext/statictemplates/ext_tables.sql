#
# Table structure for table 'static_template'
#
CREATE TABLE static_template (
  uid int(11) unsigned NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  title varchar(255) DEFAULT '' NOT NULL,
  include_static tinyblob NOT NULL,
  constants blob NOT NULL,
  config blob NOT NULL,
  editorcfg blob NOT NULL,
  description text NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Add field for static_templates in table 'sys_template'
#
CREATE TABLE sys_template (
  include_static tinyblob NOT NULL
);
