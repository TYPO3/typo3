CREATE TABLE functional_test_reset_parent (
	uid int unsigned NOT NULL auto_increment,
	title varchar(255) DEFAULT '' NOT NULL,
	PRIMARY KEY (uid)
);

CREATE TABLE functional_test_reset_child (
	parent_uid int unsigned DEFAULT 0 NOT NULL,
	value varchar(255) DEFAULT '' NOT NULL,
	PRIMARY KEY (parent_uid)
);
