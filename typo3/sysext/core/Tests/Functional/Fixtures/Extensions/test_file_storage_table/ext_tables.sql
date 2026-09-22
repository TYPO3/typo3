#
# The sys_file_storage table as it existed before TYPO3 v15, so the
# migration to config/system/file-storages.yaml can be tested.
#
CREATE TABLE sys_file_storage (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	description text,
	name varchar(255) DEFAULT '' NOT NULL,
	driver varchar(255) DEFAULT '' NOT NULL,
	configuration text,
	is_browsable tinyint(4) DEFAULT '0' NOT NULL,
	is_default tinyint(4) DEFAULT '0' NOT NULL,
	is_public tinyint(4) DEFAULT '0' NOT NULL,
	is_writable tinyint(4) DEFAULT '0' NOT NULL,
	is_online tinyint(4) DEFAULT '0' NOT NULL,
	auto_extract_metadata tinyint(4) DEFAULT '0' NOT NULL,
	processingfolder tinytext,
	PRIMARY KEY (uid)
);
