# TYPO3 Extension Manager dump 1.0
#
# Host: TYPO3_host    Database: t3_testsite
#--------------------------------------------------------


#
# Table structure for table 'index_fulltext'
#
CREATE TABLE index_fulltext (
  phash int(11) DEFAULT '0' NOT NULL,
  fulltextdata mediumtext NOT NULL,
  PRIMARY KEY (phash)
);


#
# Table structure for table 'index_grlist'
#
CREATE TABLE index_grlist (
  phash int(11) DEFAULT '0' NOT NULL,
  phash_x int(11) DEFAULT '0' NOT NULL,
  hash_gr_list int(11) DEFAULT '0' NOT NULL,
  gr_list tinytext NOT NULL,
  uniqid int(11) DEFAULT '0' NOT NULL auto_increment,
  PRIMARY KEY (uniqid),
  KEY joinkey (phash,hash_gr_list),
  KEY phash_grouping (phash_x,hash_gr_list)
);


#
# Table structure for table 'index_phash'
#
CREATE TABLE index_phash (
  phash int(11) DEFAULT '0' NOT NULL,
  phash_grouping int(11) DEFAULT '0' NOT NULL,
  cHashParams tinyblob NOT NULL,
  data_filename tinytext NOT NULL,
  data_page_id int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_reg1 int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_type tinyint(3) unsigned DEFAULT '0' NOT NULL,
  data_page_mp tinytext NOT NULL,
  gr_list tinytext NOT NULL,
  item_type tinyint(4) DEFAULT '0' NOT NULL,
  item_title tinytext NOT NULL,
  item_description tinytext NOT NULL,
  item_mtime int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  item_size int(11) DEFAULT '0' NOT NULL,
  contentHash int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  parsetime int(11) DEFAULT '0' NOT NULL,
  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  item_crdate int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (phash),
  KEY phash_grouping (phash_grouping)
);


#
# Table structure for table 'index_rel'
#
CREATE TABLE index_rel (
  phash int(11) DEFAULT '0' NOT NULL,
  wid int(11) DEFAULT '0' NOT NULL,
  count tinyint(3) unsigned DEFAULT '0' NOT NULL,
  first tinyint(3) unsigned DEFAULT '0' NOT NULL,
  freq smallint(5) unsigned DEFAULT '0' NOT NULL,
  flags tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (phash,wid),
  KEY wid (wid,phash)
);


#
# Table structure for table 'index_section'
#
CREATE TABLE index_section (
  phash int(11) DEFAULT '0' NOT NULL,
  phash_t3 int(11) DEFAULT '0' NOT NULL,
  rl0 int(11) unsigned DEFAULT '0' NOT NULL,
  rl1 int(11) unsigned DEFAULT '0' NOT NULL,
  rl2 int(11) unsigned DEFAULT '0' NOT NULL,
  page_id int(11) DEFAULT '0' NOT NULL,
  uniqid int(11) DEFAULT '0' NOT NULL auto_increment,
  PRIMARY KEY (uniqid),
  KEY joinkey (phash,rl0),
  KEY page_id (page_id),
  KEY rl0 (rl0,rl1,phash),
  KEY rl0_2 (rl0,phash)
);


#
# Table structure for table 'index_stat_search'
#
CREATE TABLE index_stat_search (
  uid int(11) DEFAULT '0' NOT NULL auto_increment,
  searchstring tinytext NOT NULL,
  searchoptions blob NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  feuser_id int(11) unsigned DEFAULT '0' NOT NULL,
  cookie varchar(10) DEFAULT '' NOT NULL,
  IP tinytext NOT NULL,
  hits int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid)
);


#
# Table structure for table 'index_stat_word'
#
CREATE TABLE index_stat_word (
  uid int(11) DEFAULT '0' NOT NULL auto_increment,
  word varchar(30) DEFAULT '' NOT NULL,
  index_stat_search_id int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY tstamp (tstamp,word)
);


#
# Table structure for table 'index_words'
#
CREATE TABLE index_words (
  wid int(11) DEFAULT '0' NOT NULL,
  baseword varchar(30) DEFAULT '' NOT NULL,
  metaphone int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (wid),
  KEY baseword (baseword,wid),
  KEY metaphone (metaphone,wid)
);