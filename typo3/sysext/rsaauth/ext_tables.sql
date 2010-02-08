#
# Table structure for table 'tx_rsaauth_keys'
#
CREATE TABLE tx_rsaauth_keys (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	key_value text,

	PRIMARY KEY (uid),
	KEY crdate (crdate)
);
