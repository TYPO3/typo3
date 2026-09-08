CREATE TABLE tx_testtablegarbagecollection_expire (
	uid int(11) unsigned NOT NULL auto_increment,
	expire_field int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid)
);

CREATE TABLE tx_testtablegarbagecollection_composite (
	identifier varchar(40) DEFAULT '' NOT NULL,
	scope varchar(40) DEFAULT '' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (identifier,scope)
);
