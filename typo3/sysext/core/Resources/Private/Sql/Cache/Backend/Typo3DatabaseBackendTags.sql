CREATE TABLE ###TAGS_TABLE### (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	tag varchar(250) DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier(191)),
	KEY cache_tag (tag(191))
) ENGINE=InnoDB;