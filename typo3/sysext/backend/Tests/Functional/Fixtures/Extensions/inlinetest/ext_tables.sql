
#
# Table structure for table 'tx_inlinetest_record'
#
CREATE TABLE tx_inlinetest_record (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	typeswitch varchar(255) DEFAULT '' NOT NULL,
	subtypeswitch varchar(255) DEFAULT '' NOT NULL,
	beforeinline varchar(255) DEFAULT '' NOT NULL,
	children varchar(255) DEFAULT '' NOT NULL,
	afterinline int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_inlinetest_inline'
#
CREATE TABLE tx_inlinetest_inline (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	parent_uid int(11) unsigned DEFAULT '0' NOT NULL,
	text text,
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY parent_node (parent_uid)
);
