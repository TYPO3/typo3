CREATE TABLE sys_redirect (
	# @todo: Declared type=input but should be something different
	hitcount int(11) DEFAULT '0' NOT NULL,
	createdby int(11) UNSIGNED DEFAULT '0' NOT NULL,
	KEY index_source (source_host(80),source_path(80))
);
