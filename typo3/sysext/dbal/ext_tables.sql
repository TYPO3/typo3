
#
# Table structure for table 'tx_dbal_debuglog'
#
CREATE TABLE tx_dbal_debuglog (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	beuser_id int(11) unsigned DEFAULT '0' NOT NULL,
	script tinytext NOT NULL,
	exec_time int(11) unsigned DEFAULT '0' NOT NULL,
	table_join tinytext NOT NULL,
	serdata text NOT NULL,
	query text NOT NULL,
	errorFlag int(11) unsigned DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY tstamp (tstamp)
);