CREATE TABLE ###CACHE_TABLE### (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	expires int(11) unsigned DEFAULT '0' NOT NULL,
	content longblob,
	PRIMARY KEY (id),
	KEY cache_id (identifier(180),expires)
) ENGINE=InnoDB;