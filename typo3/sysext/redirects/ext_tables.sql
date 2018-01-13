#
# Table structure for table 'sys_redirect'
#
CREATE TABLE sys_redirect (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	source_host varchar(255) DEFAULT '' NOT NULL,
	source_path varchar(255) DEFAULT '' NOT NULL,
	is_regexp tinyint(1) unsigned DEFAULT '0' NOT NULL,

	force_https tinyint(1) unsigned DEFAULT '0' NOT NULL,
	keep_query_parameters tinyint(1) unsigned DEFAULT '0' NOT NULL,
	target varchar(255) DEFAULT '' NOT NULL,
	target_statuscode int(11) DEFAULT '0' NOT NULL,

	hitcount int(11) DEFAULT '0' NOT NULL,
	lasthiton int(11) DEFAULT '0' NOT NULL,
	disable_hitcount tinyint(1) unsigned DEFAULT '0' NOT NULL,

	createdby int(11) unsigned DEFAULT '0' NOT NULL,
	createdon int(11) unsigned DEFAULT '0' NOT NULL,
	updatedon int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	disabled tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY index_source (source_host,source_path)
);
