CREATE TABLE a_test_table (
	uid     INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT,
	pid     INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	col1    CHAR(10) DEFAULT ''             NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
	col2    CHAR(10) CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
	col3    VARCHAR(10) DEFAULT ''          NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
	col4    VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
	col5    TEXT DEFAULT ''                 NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
	col6    TEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
	col7    MEDIUMTEXT DEFAULT ''           NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
	col8    MEDIUMTEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,
	col9    LONGTEXT DEFAULT ''             NOT NULL CHARACTER SET ascii COLLATE ascii_bin,
	col10   LONGTEXT CHARACTER SET ascii COLLATE ascii_bin DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
