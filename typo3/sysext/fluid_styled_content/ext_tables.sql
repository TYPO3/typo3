#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	bullets_type tinyint(3) unsigned DEFAULT '0' NOT NULL,
	uploads_description tinyint(1) unsigned DEFAULT '0' NOT NULL,
	uploads_type tinyint(3) unsigned DEFAULT '0' NOT NULL,
	assets int(11) unsigned DEFAULT '0' NOT NULL
);
