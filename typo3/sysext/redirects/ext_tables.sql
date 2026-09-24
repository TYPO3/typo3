CREATE TABLE sys_redirect (
	redirect_type varchar(100) DEFAULT 'default',
	createdby int(11) UNSIGNED DEFAULT '0' NOT NULL,
	KEY index_source (source_host(80),source_path(80))
);
