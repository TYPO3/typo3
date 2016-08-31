#
# Table structure for table 'index_fulltext'
#
# Differences compared to original definition in EXT:indexed_search are as follows:
# - Add new mediumtext field "metaphonedata"
# - Add new FULLTEXT index "fulltextdata"
# - Add new FULLTEXT index "metaphonedata"
# - Change table engine from InnoDB to MyISAM (required for FULLTEXT indexing)
CREATE TABLE index_fulltext (
  phash int(11) DEFAULT '0' NOT NULL,
  fulltextdata mediumtext,
  metaphonedata mediumtext,
  PRIMARY KEY (phash),
  FULLTEXT fulltextdata (fulltextdata),
  FULLTEXT metaphonedata (metaphonedata)
) ENGINE=MyISAM;
