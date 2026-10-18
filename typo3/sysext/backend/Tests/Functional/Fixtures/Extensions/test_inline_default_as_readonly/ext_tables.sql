CREATE TABLE tx_testinlinedefaultasreadonly_parent (
	name varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tx_testinlinedefaultasreadonly_child (
	parent int(11) unsigned DEFAULT '0' NOT NULL,
	value varchar(255) DEFAULT '' NOT NULL
);
