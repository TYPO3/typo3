-- CREATE TABLE in static data import is ignored
CREATE TABLE anotherTestTable (
	uid   INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(50) DEFAULT ''          NOT NULL
);

INSERT INTO aTestTable VALUES (NULL, 0, 0, 0, 0);
INSERT INTO `aTestTable` VALUES (NULL, 1, 1, 1, 1);
