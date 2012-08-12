#
# Table structure for table 'sys_ter'
#
CREATE TABLE sys_ter (
  uid int(11) unsigned NOT NULL auto_increment,
  title varchar(150) NOT NULL default '',
  description mediumtext,
  wsdl_url varchar(100) NOT NULL default '',
  mirror_url varchar(100) NOT NULL default '',
  lastUpdated int(11) unsigned DEFAULT '0' NOT NULL,
  extCount int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid)
);

#
# Table structure for table 'cache_extensions'
#
CREATE TABLE cache_extensions (
  uid int(11) NOT NULL auto_increment,
  extkey varchar(60) NOT NULL default '',
  repository int(11) unsigned NOT NULL default '1',
  version varchar(10) NOT NULL default '',
  alldownloadcounter int(11) unsigned NOT NULL default '0',
  downloadcounter int(11) unsigned NOT NULL default '0',
  title varchar(150) NOT NULL default '',
  description mediumtext,
  state int(4) NOT NULL default '0',
  reviewstate int(4) NOT NULL default '0',
  category int(4) NOT NULL default '0',
  lastuploaddate int(11) unsigned NOT NULL default '0',
  dependencies mediumtext,
  authorname varchar(100) NOT NULL default '',
  authoremail varchar(100) NOT NULL default '',
  ownerusername varchar(50) NOT NULL default '',
  t3xfilemd5 varchar(35) NOT NULL default '',
  uploadcomment mediumtext,
  authorcompany varchar(100) NOT NULL default '',
  intversion int(11) NOT NULL default '0',
  lastversion int(3) NOT NULL default '0',
  lastreviewedversion int(3) NOT NULL default '0',
  PRIMARY KEY (uid),
  UNIQUE versionextrepo (extkey,version,repository)
);