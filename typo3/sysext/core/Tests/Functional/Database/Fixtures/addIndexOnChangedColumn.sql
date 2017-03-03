CREATE TABLE a_test_table (
	pid   BIGINT(11) UNSIGNED             NOT NULL,
	title MEDIUMTEXT,
	UNIQUE title (title(40))
);
