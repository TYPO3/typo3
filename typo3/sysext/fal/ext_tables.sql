
CREATE TABLE sys_files (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	mount int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	file_name tinytext,
	file_path tinytext,
	file_size int(11) DEFAULT '0' NOT NULL,
	file_mtime int(11) DEFAULT '0' NOT NULL,
	file_inode int(11) DEFAULT '0' NOT NULL,
	file_ctime int(11) DEFAULT '0' NOT NULL,
	file_hash tinytext,
	file_mime_type tinytext,
	file_mime_subtype tinytext,
	file_type tinytext,
	file_type_version tinytext,
	file_usage int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE sys_files_mounts (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title varchar(50) DEFAULT '' NOT NULL,
	alias varchar(20) DEFAULT '' NOT NULL,
	storage_backend varchar(50) DEFAULT '' NOT NULL,
	backend_configuration mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE sys_files_usage_mm (
uid_local int(11) DEFAULT '0' NOT NULL,
uid_foreign int(11) DEFAULT '0' NOT NULL,
tablenames varchar(30) DEFAULT '' NOT NULL,
ident varchar(255) DEFAULT '' NOT NULL,
structure_path varchar(255) DEFAULT '' NOT NULL,
sorting int(11) DEFAULT '0' NOT NULL,
sorting_foreign int(11) DEFAULT '0' NOT NULL,
KEY uid_local (uid_local),
KEY uid_foreign (uid_foreign)
);

CREATE TABLE tt_content (
	image_rel int(11) DEFAULT '0' NOT NULL,
	media_rel int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE pages (
	media_rel int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE pages_language_overlay (
	media_rel int(11) DEFAULT '0' NOT NULL
);

