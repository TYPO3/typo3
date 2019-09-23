CREATE TABLE tx_linkvalidator_link (
	uid int(11) NOT NULL auto_increment,
	record_uid int(11) DEFAULT '0' NOT NULL,
	record_pid int(11) DEFAULT '0' NOT NULL,
	language int(11) DEFAULT '-1' NOT NULL,
	headline varchar(255) DEFAULT '' NOT NULL,
	field varchar(255) DEFAULT '' NOT NULL,
	table_name varchar(255) DEFAULT '' NOT NULL,
	element_type varchar(255) DEFAULT '' NOT NULL,
	link_title text,
	url text,
	url_response text,
	last_check int(11) DEFAULT '0' NOT NULL,
	link_type varchar(50) DEFAULT '' NOT NULL,
	needs_recheck int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid)
);
