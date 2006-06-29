
#
# Table structure for table 'tx_dbal_debuglog'
#
CREATE TABLE tx_dbal_debuglog (
	uid int(11) unsigned NOT NULL auto_increment,
	tstamp int(11) unsigned DEFAULT '0',
	beuser_id int(11) unsigned DEFAULT '0',
	script varchar(255) NOT NULL DEFAULT '',
	exec_time int(11) unsigned DEFAULT '0',
	table_join tinytext,
	serdata blob,
	query text NOT NULL,
	errorFlag int(11) unsigned DEFAULT '0',

	PRIMARY KEY (uid),
	KEY tstamp (tstamp)
);

#
# Table structure for table 'tx_dbal_debuglog_where'
#
CREATE TABLE tx_dbal_debuglog_where (
	uid int(11) unsigned NOT NULL auto_increment,
	tstamp int(11) unsigned DEFAULT '0',
	beuser_id int(11) unsigned DEFAULT '0',
	script tinytext NOT NULL,
	tablename tinytext NOT NULL,
	whereclause text NOT NULL,

	PRIMARY KEY (uid),
	KEY tstamp (tstamp)
);
