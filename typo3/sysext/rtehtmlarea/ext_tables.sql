#
# Table structure for extension 'rtehtmlarea'
#

CREATE TABLE tx_rtehtmlarea_acronym (
  uid int(11) unsigned NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  starttime int(11) unsigned DEFAULT '0' NOT NULL,
  endtime int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  type tinyint(3) unsigned DEFAULT '1' NOT NULL,
  term varchar(255) DEFAULT '' NOT NULL,
  acronym varchar(255) DEFAULT '' NOT NULL,
  static_lang_isocode int(11) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

