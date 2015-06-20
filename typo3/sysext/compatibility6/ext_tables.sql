#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	altText text,
	imagecaption text,
	imagecaption_position varchar(6) DEFAULT '' NOT NULL,
	image_link text,
	image_frames int(11) unsigned DEFAULT '0' NOT NULL,
	longdescURL text,
	titleText text
);
