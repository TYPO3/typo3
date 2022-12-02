#
# Table structure for table 'sys_reaction'
#
CREATE TABLE sys_reaction (
	name varchar(100) DEFAULT '' NOT NULL,
	reaction_type varchar(255) DEFAULT '' NOT NULL,
	secret varchar(255) DEFAULT '' NOT NULL,
	impersonate_user int(11) unsigned DEFAULT '0' NOT NULL,
	table_name varchar(255) DEFAULT '' NOT NULL,
	storage_pid int(11) unsigned DEFAULT '0' NOT NULL,

	UNIQUE identifier_key (identifier),
	KEY index_source (reaction_type(5))
);
