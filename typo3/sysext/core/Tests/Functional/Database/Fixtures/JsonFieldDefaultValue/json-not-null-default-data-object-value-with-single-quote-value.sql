CREATE TABLE a_textfield_test_table
(
	uid 			INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	pid 			INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	testfield JSON NOT NULL DEFAULT '{"key1": "value1", "key2": 123, "key3": "value with a '' single quote"}',

	PRIMARY KEY (uid),
	KEY parent (pid)
);
