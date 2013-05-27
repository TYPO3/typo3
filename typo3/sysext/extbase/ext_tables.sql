#
# Table structure for table 'tx_extbase_cache_reflection'
#
CREATE TABLE tx_extbase_cache_reflection (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	content mediumtext,
	tags mediumtext,
	lifetime int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_extbase_cache_reflection_tags'
#
CREATE TABLE tx_extbase_cache_reflection_tags (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(128) DEFAULT '' NOT NULL,
	tag varchar(128) DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier),
	KEY cache_tag (tag)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_extbase_cache_object'
#
CREATE TABLE tx_extbase_cache_object (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(250) DEFAULT '' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	content mediumtext,
	tags mediumtext,
	lifetime int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_extbase_cache_object_tags'
#
CREATE TABLE tx_extbase_cache_object_tags (
	id int(11) unsigned NOT NULL auto_increment,
	identifier varchar(128) DEFAULT '' NOT NULL,
	tag varchar(128) DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	KEY cache_id (identifier),
	KEY cache_tag (tag)
) ENGINE=InnoDB;

#
# Add field 'tx_extbase_type' to table 'fe_users'
#
CREATE TABLE fe_users (
	tx_extbase_type varchar(255) DEFAULT '' NOT NULL,
);

#
# Add field 'tx_extbase_type' to table 'fe_groups'
#
CREATE TABLE fe_groups (
	tx_extbase_type varchar(255) DEFAULT '' NOT NULL,
);