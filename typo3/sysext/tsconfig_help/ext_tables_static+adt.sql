# TYPO3 Extension Manager dump 1.1
#
# Host: localhost    Database: t3_devdb
#--------------------------------------------------------


#
# Table structure for table "static_tsconfig_help"
#
DROP TABLE IF EXISTS static_tsconfig_help;
CREATE TABLE static_tsconfig_help (
  uid int(11) NOT NULL auto_increment,
  guide int(11) DEFAULT '0' NOT NULL,
  md5hash varchar(32) DEFAULT '' NOT NULL,
  description text NOT NULL,
  obj_string tinytext NOT NULL,
  appdata blob NOT NULL,
  title tinytext NOT NULL,
  PRIMARY KEY (uid),
  KEY guide (guide,md5hash)
);


