#
# Table structure for table 'sys_note'
#
CREATE TABLE sys_note (
  subject varchar(255) DEFAULT '' NOT NULL,
  message text,
  personal tinyint(3) unsigned DEFAULT '0' NOT NULL,
  category tinyint(3) unsigned DEFAULT '0' NOT NULL,
  position int(11) DEFAULT '0' NOT NULL
);
