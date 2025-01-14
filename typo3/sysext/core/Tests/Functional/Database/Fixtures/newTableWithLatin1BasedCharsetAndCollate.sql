CREATE TABLE a_test_table (
	uid     INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT,
	pid     INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
	col1    CHAR(10) DEFAULT ''             NOT NULL CHARACTER SET latin1 COLLATE latin1_swedish_ci,
	col2    CHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '' NOT NULL,
	col3    VARCHAR(10) DEFAULT ''          NOT NULL CHARACTER SET latin1 COLLATE latin1_swedish_ci,
	col4    VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '' NOT NULL,
	col5    TEXT DEFAULT ''                 NOT NULL CHARACTER SET latin1 COLLATE latin1_swedish_ci,
	col6    TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '' NOT NULL,
	col7    MEDIUMTEXT DEFAULT ''           NOT NULL CHARACTER SET latin1 COLLATE latin1_swedish_ci,
	col8    MEDIUMTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '' NOT NULL,
	col9    LONGTEXT DEFAULT ''             NOT NULL CHARACTER SET latin1 COLLATE latin1_swedish_ci,
	col10   LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
