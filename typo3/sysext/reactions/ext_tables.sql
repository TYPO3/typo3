CREATE TABLE sys_reaction (
	# group fields, but rely on the integer format, so default format (text) gets overridden here
	impersonate_user int(11) unsigned DEFAULT '0' NOT NULL,
	storage_pid int(11) unsigned DEFAULT '0' NOT NULL,

	UNIQUE identifier_key (identifier),
	KEY index_source (reaction_type(5))
);
