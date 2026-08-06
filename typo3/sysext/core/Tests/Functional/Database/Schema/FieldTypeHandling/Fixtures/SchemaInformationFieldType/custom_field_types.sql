CREATE TABLE a_test_table (
	uid             INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT,
	pid             INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	json_field      JSON,
	uuid_field      UUID,
	varchar_field   VARCHAR(36) DEFAULT ''          NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);
