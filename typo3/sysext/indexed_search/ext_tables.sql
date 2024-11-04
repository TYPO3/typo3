# Define table and fields since it has no TCA
CREATE TABLE index_phash (
  phash varchar(32) NOT NULL,
  phash_grouping varchar(32) NOT NULL,
  static_page_arguments blob,
  data_filename varchar(1024) DEFAULT '' NOT NULL,
  data_page_id int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_type int(11) unsigned DEFAULT '0' NOT NULL,
  data_page_mp varchar(255) DEFAULT '' NOT NULL,
  gr_list varchar(255) DEFAULT '' NOT NULL,
  item_type varchar(5) DEFAULT '' NOT NULL,
  item_title varchar(255) DEFAULT '' NOT NULL,
  item_description varchar(255) DEFAULT '' NOT NULL,
  item_mtime int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  item_size int(11) DEFAULT '0' NOT NULL,
  contentHash varchar(32) NOT NULL,
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
);

# Define table and fields since it has no TCA
CREATE TABLE index_fulltext (
  phash varchar(32) NOT NULL,
  fulltextdata mediumtext,
  PRIMARY KEY (phash)
);

# Define table and fields since it has no TCA
CREATE TABLE index_rel (
  phash varchar(32) NOT NULL,
  wid varchar(32) NOT NULL,
  count tinyint(3) unsigned DEFAULT '0' NOT NULL,
  first int(11) unsigned DEFAULT '0' NOT NULL,
  freq smallint(5) unsigned DEFAULT '0' NOT NULL,
  flags tinyint(3) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (phash,wid),
  KEY wid (wid,phash)
);

# Define table and fields since it has no TCA
CREATE TABLE index_words (
  wid varchar(32) NOT NULL,
  baseword varchar(60) DEFAULT '' NOT NULL,
  is_stopword tinyint(3) DEFAULT '0' NOT NULL,
  PRIMARY KEY (wid),
  KEY baseword (baseword)
);

# Define table and fields since it has no TCA
CREATE TABLE index_section (
  uniqid int(11) NOT NULL auto_increment,
  phash varchar(32) NOT NULL,
  phash_t3 varchar(32) NOT NULL,
  rl0 int(11) unsigned DEFAULT '0' NOT NULL,
  rl1 int(11) unsigned DEFAULT '0' NOT NULL,
  rl2 int(11) unsigned DEFAULT '0' NOT NULL,
  page_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uniqid),
  KEY joinkey (phash,rl0),
  KEY page_id (page_id),
  KEY rl0 (rl0,rl1,phash),
  KEY rl0_2 (rl0,phash)
);

# Define table and fields since it has no TCA
CREATE TABLE index_grlist (
  uniqid int(11) NOT NULL auto_increment,
  phash varchar(32) NOT NULL,
  phash_x varchar(32) NOT NULL,
  hash_gr_list varchar(32) NOT NULL,
  gr_list varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (uniqid),
  KEY joinkey (phash,hash_gr_list),
  KEY phash_grouping (phash_x,hash_gr_list)
);

CREATE TABLE index_config (
  # @todo: Change TCA type from input to something better
  set_id int(11) DEFAULT '0' NOT NULL,
  # @todo: Completely unused?!
  session_data mediumtext,
  # @todo: type=group fields, but rely on integer.
  alternative_source_pid int(11) unsigned DEFAULT '0' NOT NULL,
);

# Define table and fields since it has no TCA
CREATE TABLE index_stat_word (
  uid int(11) NOT NULL auto_increment,
  word varchar(50) DEFAULT '' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  pageid int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY tstamp (tstamp,word)
);
