# Define table and fields since it has no TCA
CREATE TABLE tx_expressionbuildertest (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	aField text,
	aCsvField text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

# Define table and fields since it has no TCA
CREATE TABLE tx_expressionbuildertest_varchar (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	aField varchar(100) DEFAULT '' NOT NULL,
	aCsvField varchar(100) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

# Define table and fields since it has no TCA
CREATE TABLE tx_expressionbuildertest_integer (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	aField varchar(100) DEFAULT '' NOT NULL,
	aCsvField INT(11) DEFAULT 0 NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
