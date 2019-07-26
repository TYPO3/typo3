#
# Table structure for table 'sys_redirect'
#
CREATE TABLE sys_redirect (
	source_host varchar(255) DEFAULT '' NOT NULL,
	source_path varchar(2048) DEFAULT '' NOT NULL,
	is_regexp tinyint(1) unsigned DEFAULT '0' NOT NULL,

	force_https tinyint(1) unsigned DEFAULT '0' NOT NULL,
	respect_query_parameters tinyint(1) unsigned DEFAULT '0' NOT NULL,
	keep_query_parameters tinyint(1) unsigned DEFAULT '0' NOT NULL,
	target varchar(2048) DEFAULT '' NOT NULL,
	target_statuscode int(11) DEFAULT '307' NOT NULL,

	hitcount int(11) DEFAULT '0' NOT NULL,
	lasthiton int(11) DEFAULT '0' NOT NULL,
	disable_hitcount tinyint(1) unsigned DEFAULT '0' NOT NULL,

	KEY index_source (source_host(80),source_path(80))
);
