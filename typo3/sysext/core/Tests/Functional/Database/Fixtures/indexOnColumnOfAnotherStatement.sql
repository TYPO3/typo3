CREATE TABLE a_test_table (
	uid     INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT,
	pid     INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	title   VARCHAR(50) DEFAULT ''          NOT NULL,
	sorting INT(11) UNSIGNED DEFAULT '0'    NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE a_test_table (
	KEY sorting (sorting)
);
