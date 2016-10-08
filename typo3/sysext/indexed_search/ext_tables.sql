
#
# Table structure for table 'index_phash'
#
CREATE TABLE index_phash (
  phash int(11) DEFAULT '0' NOT NULL,
  phash_grouping int(11) DEFAULT '0' NOT NULL,
  cHashParams blob,
  data_filename varchar(1024) DEFAULT '' NOT NULL,
  data_page_id int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_reg1 int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_type int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_mp varchar(255) DEFAULT '' NOT NULL,
  gr_list varchar(255) DEFAULT '' NOT NULL,
  item_type varchar(5) DEFAULT '' NOT NULL,
  item_title varchar(255) DEFAULT '' NOT NULL,
  item_description varchar(255) DEFAULT '' NOT NULL,
  item_mtime int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  item_size int(11) DEFAULT '0' NOT NULL,
  contentHash int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  parsetime int(11) DEFAULT '0' NOT NULL,
  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  item_crdate int(11) DEFAULT '0' NOT NULL,
  externalUrl tinyint(3) DEFAULT '0' NOT NULL,
  recordUid int(11) DEFAULT '0' NOT NULL,
  freeIndexUid int(11) DEFAULT '0' NOT NULL,
  freeIndexSetId int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (phash),
  KEY phash_grouping (phash_grouping),
  KEY freeIndexUid (freeIndexUid)
) ENGINE=InnoDB;

#
# Table structure for table 'index_fulltext'
#
CREATE TABLE index_fulltext (
  phash int(11) DEFAULT '0' NOT NULL,
  fulltextdata mediumtext,
  metaphonedata mediumtext NOT NULL,
  PRIMARY KEY (phash)
) ENGINE=InnoDB;

#
# Table structure for table 'index_rel'
#
CREATE TABLE index_rel (
  phash int(11) DEFAULT '0' NOT NULL,
  wid int(11) DEFAULT '0' NOT NULL,
  count tinyint(3) unsigned DEFAULT '0' NOT NULL,
  first int(11) unsigned DEFAULT '0' NOT NULL,
  freq smallint(5) unsigned DEFAULT '0' NOT NULL,
  flags tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (phash,wid),
  KEY wid (wid,phash)
) ENGINE=InnoDB;

#
# Table structure for table 'index_words'
#
CREATE TABLE index_words (
  wid int(11) DEFAULT '0' NOT NULL,
  baseword varchar(60) DEFAULT '' NOT NULL,
  metaphone int(11) DEFAULT '0' NOT NULL,
  is_stopword tinyint(3) DEFAULT '0' NOT NULL,
  PRIMARY KEY (wid),
  KEY baseword (baseword,wid),
  KEY metaphone (metaphone,wid)
) ENGINE=InnoDB;

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
  uniqid int(11) NOT NULL auto_increment,
  PRIMARY KEY (uniqid),
  KEY joinkey (phash,rl0),
#  KEY phash_pid (phash,page_id),
  KEY page_id (page_id),
  KEY rl0 (rl0,rl1,phash),
  KEY rl0_2 (rl0,phash)
) ENGINE=InnoDB;

#
# Table structure for table 'index_grlist'
#
CREATE TABLE index_grlist (
  phash int(11) DEFAULT '0' NOT NULL,
  phash_x int(11) DEFAULT '0' NOT NULL,
  hash_gr_list int(11) DEFAULT '0' NOT NULL,
  gr_list varchar(255) DEFAULT '' NOT NULL,
  uniqid int(11) NOT NULL auto_increment,
  PRIMARY KEY (uniqid),
  KEY joinkey (phash,hash_gr_list),
  KEY phash_grouping (phash_x,hash_gr_list)
) ENGINE=InnoDB;

#
# Table structure for table 'index_stat_search'
#
CREATE TABLE index_stat_search (
  uid int(11) NOT NULL auto_increment,
  searchstring varchar(255) DEFAULT '' NOT NULL,
  searchoptions blob,
  tstamp int(11) DEFAULT '0' NOT NULL,
  feuser_id int(11) unsigned DEFAULT '0' NOT NULL,
  cookie varchar(32) DEFAULT '' NOT NULL,
  IP varchar(255) DEFAULT '' NOT NULL,
  hits int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid)
) ENGINE=InnoDB;

#
# Table structure for table 'index_debug'
#
CREATE TABLE index_debug (
  phash int(11) DEFAULT '0' NOT NULL,
  debuginfo mediumtext,
  PRIMARY KEY (phash)
);

#
# Table structure for table 'index_config'
#
CREATE TABLE index_config (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  cruser_id int(11) DEFAULT '0' NOT NULL,
  hidden tinyint(4) DEFAULT '0' NOT NULL,
  starttime int(11) DEFAULT '0' NOT NULL,

  set_id int(11) DEFAULT '0' NOT NULL,
  session_data mediumtext,

  title varchar(255) DEFAULT '' NOT NULL,
  description text,
  type varchar(30) DEFAULT '' NOT NULL,
  depth int(11) unsigned DEFAULT '0' NOT NULL,
  table2index varchar(255) DEFAULT '' NOT NULL,
  alternative_source_pid int(11) unsigned DEFAULT '0' NOT NULL,
  get_params varchar(255) DEFAULT '' NOT NULL,
  fieldlist varchar(255) DEFAULT '' NOT NULL,
  externalUrl varchar(255) DEFAULT '' NOT NULL,
  indexcfgs text,
  chashcalc tinyint(3) unsigned DEFAULT '0' NOT NULL,
  filepath varchar(255) DEFAULT '' NOT NULL,
  extensions varchar(255) DEFAULT '' NOT NULL,

  timer_next_indexing int(11) DEFAULT '0' NOT NULL,
  timer_frequency int(11) DEFAULT '0' NOT NULL,
  timer_offset int(11) DEFAULT '0' NOT NULL,
  url_deny text,
  recordsbatch int(11) DEFAULT '0' NOT NULL,
  records_indexonchange tinyint(4) DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);


#
# Table structure for table 'index_stat_word'
#
CREATE TABLE index_stat_word (
  uid int(11) NOT NULL auto_increment,
  word varchar(30) DEFAULT '' NOT NULL,
  index_stat_search_id int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  pageid int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY tstamp (tstamp,word)
) ENGINE=InnoDB;
