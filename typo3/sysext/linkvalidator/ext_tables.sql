CREATE TABLE tx_linkvalidator_links (
	uid int(11) NOT NULL auto_increment,
	recuid int(11) DEFAULT '0' NOT NULL,
	recpid int(11) DEFAULT '0' NOT NULL,
	headline varchar(255) DEFAULT '' NOT NULL,
	field varchar(255) DEFAULT '' NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL,
	linktitle text,
	url text,
	urlresponse text,
	lastcheck int(11) DEFAULT '0' NOT NULL,
	typelinks varchar(50) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid)
);