CREATE TABLE a_test_table (
	uid bigint unsigned NOT NULL auto_increment,
	pid int(11) unsigned NOT NULL default 0,
	title varchar(100) NOT NULL default '',

	PRIMARY KEY (uid)
);
