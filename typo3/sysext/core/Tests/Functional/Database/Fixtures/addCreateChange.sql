CREATE TABLE aTestTable (
	pid   BIGINT(11) UNSIGNED             NOT NULL,
	title VARCHAR(50) DEFAULT ''          NOT NULL,
	UNIQUE title (title)
);

CREATE TABLE anotherTestTable (
	uid   INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(50) DEFAULT ''          NOT NULL
);
