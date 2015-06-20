#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	header_position varchar(6) DEFAULT '' NOT NULL,
	image_compression tinyint(3) unsigned DEFAULT '0' NOT NULL,
	image_effects tinyint(3) unsigned DEFAULT '0' NOT NULL,
	image_noRows tinyint(3) unsigned DEFAULT '0' NOT NULL,
	section_frame int(11) unsigned DEFAULT '0' NOT NULL,
	spaceAfter smallint(5) unsigned DEFAULT '0' NOT NULL,
	spaceBefore smallint(5) unsigned DEFAULT '0' NOT NULL,
	table_bgColor int(11) unsigned DEFAULT '0' NOT NULL,
	table_border tinyint(3) unsigned DEFAULT '0' NOT NULL,
	table_cellpadding tinyint(3) unsigned DEFAULT '0' NOT NULL,
	table_cellspacing tinyint(3) unsigned DEFAULT '0' NOT NULL
);
