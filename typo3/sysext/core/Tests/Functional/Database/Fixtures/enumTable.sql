CREATE TABLE a_test_table (
	uid     INT(11) UNSIGNED               			NOT NULL AUTO_INCREMENT,
	pid     INT(11) UNSIGNED DEFAULT '0'    		NOT NULL,
	test1   enum('', 'v4', 'v6') DEFAULT 'v4' 	NOT NULL,

	PRIMARY KEY (uid),
) ENGINE = InnoDB;
