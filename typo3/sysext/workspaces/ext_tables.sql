# Define table and fields since it has no TCA
CREATE TABLE sys_preview (
  keyword varchar(32) DEFAULT '' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  endtime int(11) DEFAULT '0' NOT NULL,
  config text,
  PRIMARY KEY (keyword)
);
