#
# Table structure for table 'tx_extbase_cache_reflection'
#
CREATE TABLE tx_extbase_cache_reflection (
  id int(11) unsigned NOT NULL auto_increment,
  identifier varchar(250) DEFAULT '' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  content mediumtext,
  tags mediumtext,
  lifetime int(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY cache_id (identifier)
) ENGINE=InnoDB;