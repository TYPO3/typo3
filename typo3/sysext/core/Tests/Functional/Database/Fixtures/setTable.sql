CREATE TABLE a_test_table (
	uid     INT(11) UNSIGNED               			NOT NULL AUTO_INCREMENT,
	pid     INT(11) UNSIGNED DEFAULT '0'    		NOT NULL,
	test1   set('a', 'b', 'c', 'd') DEFAULT 'a' NOT NULL,

	PRIMARY KEY (uid),
) ENGINE = InnoDB;
