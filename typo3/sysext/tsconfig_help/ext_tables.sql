#
# Table structure for table 'static_tsconfig_help'
#
CREATE TABLE static_tsconfig_help (
  uid int(11) NOT NULL auto_increment,
  guide int(11) DEFAULT '0' NOT NULL,
  md5hash varchar(32) DEFAULT '' NOT NULL,
  description text,
  obj_string varchar(255) DEFAULT '' NOT NULL,
  appdata blob,
  title varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (uid),
  KEY guide (guide,md5hash)
);

