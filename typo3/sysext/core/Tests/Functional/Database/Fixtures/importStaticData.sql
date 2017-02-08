-- CREATE TABLE in static data import is ignored
CREATE TABLE another_test_table (
	uid   INT(11) UNSIGNED                NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(50) DEFAULT ''          NOT NULL
);

INSERT INTO a_test_table VALUES (NULL, 0, 0, 0, 0, 'foo');
INSERT INTO `a_test_table` VALUES (NULL, 1, 1, 1, 1, 'bar');
