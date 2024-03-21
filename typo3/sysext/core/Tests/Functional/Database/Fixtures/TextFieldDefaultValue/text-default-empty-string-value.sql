CREATE TABLE a_textfield_test_table
(
	uid 			INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	pid 			INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	testfield TEXT DEFAULT '',

	PRIMARY KEY (uid),
	KEY parent (pid)
);
