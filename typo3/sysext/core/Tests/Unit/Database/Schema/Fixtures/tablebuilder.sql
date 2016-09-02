#
# Table structure for TableBuilder test
# This table is formatted in different styles by intention!
#
CREATE TABLE aTestTable (
	-- AUTO_INCREMENT + DEFAULT '0' is invalid, combination is here to check
	-- that the tablebuilder ignores the default value in this combination.
  uid INT(11) DEFAULT '0' NOT NULL AUTO_INCREMENT,
  pid INT(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT 0 NOT NULL,
	deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	`TSconfig` text,
	no_cache int(10) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
  UNIQUE `parent` (pid,`deleted`,sorting),
	KEY noCache (`no_cache`),
	KEY substring (TSconfig(80)),
	FOREIGN KEY fk_overlay (uid) REFERENCES pages_language_overlay(pid)
) ENGINE = MyISAM DEFAULT CHARACTER SET latin1 COLLATE latin1_german_cs ROW_FORMAT DYNAMIC AUTO_INCREMENT=1;
