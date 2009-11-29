#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_openid_openid varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_openid_openid varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_openid_assoc_store'.
#
CREATE TABLE tx_openid_assoc_store (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	expires int(11) unsigned DEFAULT '0' NOT NULL,
	server_url varchar(2047) DEFAULT '' NOT NULL,
	assoc_handle varchar(255) DEFAULT '' NOT NULL,
	content blob,

	PRIMARY KEY (uid),
	KEY assoc_handle (assoc_handle(8)),
	KEY expires (expires)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_openid_nonce_store'.
#
CREATE TABLE tx_openid_nonce_store (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	server_url varchar(2047) DEFAULT '' NOT NULL,
	salt char(40) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY nonce (server_url(255),tstamp,salt),
	KEY crdate (crdate)
) ENGINE=InnoDB;
