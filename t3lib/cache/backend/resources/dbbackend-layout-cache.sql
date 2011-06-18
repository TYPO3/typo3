CREATE TABLE ###CACHE_TABLE### (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	content mediumblob,
	lifetime int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier)
) ENGINE=InnoDB;