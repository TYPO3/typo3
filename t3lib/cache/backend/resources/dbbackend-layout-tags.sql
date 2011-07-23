CREATE TABLE ###TAGS_TABLE### (
	id int(11) unsigned NOT NULL auto_increment,
	tag varchar(250) DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_tag (tag)
) ENGINE=InnoDB;
