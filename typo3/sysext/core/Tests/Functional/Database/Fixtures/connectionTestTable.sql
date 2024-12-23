CREATE TABLE datetime_tests (
	mutable_object DATETIME,
	immutable_object DATETIME,
);

CREATE TABLE string_tests (
	uid int NOT NULL AUTO_INCREMENT,
	fixed_title char(100) DEFAULT '' NOT NULL,
	flexible_title varchar(100) DEFAULT '' NOT NULL,
	PRIMARY KEY (uid)
);
