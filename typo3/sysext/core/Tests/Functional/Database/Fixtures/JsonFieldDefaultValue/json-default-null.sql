CREATE TABLE a_textfield_test_table
(
	uid 			INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	pid 			INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	testfield JSON DEFAULT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
