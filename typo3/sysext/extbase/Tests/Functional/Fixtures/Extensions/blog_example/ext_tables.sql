#
# Table structure for table 'tx_blogexample_domain_model_blog'
#
CREATE TABLE tx_blogexample_domain_model_blog (
	title varchar(255) DEFAULT '' NOT NULL,
	subtitle varchar(255) DEFAULT '',
	description text NOT NULL,
	logo tinyblob NOT NULL,
	administrator int(11) DEFAULT '0' NOT NULL,

	posts varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_blogexample_domain_model_post'
#
CREATE TABLE tx_blogexample_domain_model_post (
	blog int(11) DEFAULT '0' NOT NULL,

	title varchar(255) DEFAULT '' NOT NULL,
	date int(11) DEFAULT '0' NOT NULL,
	author int(11) DEFAULT '0' NOT NULL,
	second_author int(11) DEFAULT '0' NOT NULL,
	reviewer int(11) DEFAULT '0' NOT NULL,
	content text NOT NULL,
	tags int(11) unsigned DEFAULT '0' NOT NULL,
	comments int(11) unsigned DEFAULT '0' NOT NULL,
	related_posts int(11) unsigned DEFAULT '0' NOT NULL,
	additional_name varchar(255) DEFAULT '' NOT NULL,
	additional_info int(11) DEFAULT '0' NOT NULL,
	additional_comments varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_blogexample_domain_model_comment'
#
CREATE TABLE tx_blogexample_domain_model_comment (
	post int(11) DEFAULT '0' NOT NULL,

	date datetime,
	author varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	content text NOT NULL
);

#
# Table structure for table 'tx_blogexample_domain_model_person'
#
CREATE TABLE tx_blogexample_domain_model_person (
	firstname varchar(255) DEFAULT '' NOT NULL,
	lastname varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	tags int(11) unsigned DEFAULT '0' NOT NULL,
	tags_special int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_blogexample_domain_model_tag'
#
CREATE TABLE tx_blogexample_domain_model_tag (
	name varchar(255) DEFAULT '' NOT NULL,
	posts int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_blogexample_domain_model_tag_mm'
#
CREATE TABLE tx_blogexample_domain_model_tag_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	tablenames varchar(255) DEFAULT '' NOT NULL,
	fieldname varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_blogexample_post_tag_mm'
#
CREATE TABLE tx_blogexample_post_tag_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_blogexample_post_post_mm'
#
CREATE TABLE tx_blogexample_post_post_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_blogexample_domain_model_dateexample'
#
CREATE TABLE tx_blogexample_domain_model_dateexample (
	datetime_int int(11) DEFAULT '0' NOT NULL,
	datetime_text varchar(255) DEFAULT '' NOT NULL,
	datetime_datetime datetime
);

#
# Table structure for table 'tx_blogexample_domain_model_info'
#
CREATE TABLE tx_blogexample_domain_model_info (
	name varchar(255) DEFAULT '' NOT NULL,
	post int(11) DEFAULT '0' NOT NULL
);

# Table structure for table 'tx_blogexample_domain_model_datetimeimmutableexample'
#
CREATE TABLE tx_blogexample_domain_model_datetimeimmutableexample (
	datetime_immutable_int int(11) DEFAULT '0' NOT NULL,
	datetime_immutable_text varchar(255) DEFAULT '' NOT NULL,
	datetime_immutable_datetime datetime
);
