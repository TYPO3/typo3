# MySQL dump 8.14
#
# Host: localhost    Database: t3_dummy
#--------------------------------------------------------
# Server version	3.23.40

#
# Table structure for table 'tt_content_records_MM'
#

CREATE TABLE tt_content_records_mm (
  uid_local int(11) unsigned NOT NULL default '0',
  uid_foreign int(11) unsigned NOT NULL default '0',
  tablenames varchar(30) NOT NULL default '',
  sorting int(11) unsigned NOT NULL default '0',
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
) TYPE=MyISAM;

#
# Table structure for table 'tt_content_media_MM'
#

CREATE TABLE tt_content_media_mm (
  uid_local int(11) unsigned NOT NULL default '0',
  uid_foreign varchar(60) NOT NULL default '',
  sorting int(11) unsigned NOT NULL default '0',
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
) TYPE=MyISAM;

