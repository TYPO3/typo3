#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata (
	visible int(11) unsigned DEfAULT '1' NOT NULL,
	status varchar(24) DEFAULT '' NOT NULL,
	keywords text NOT NULL,
	caption varchar(255) DEFAULT '' NOT NULL,
	creator_tool varchar(255) DEFAULT '' NOT NULL,
	download_name varchar(255) DEFAULT '' NOT NULL,
	creator varchar(255) DEFAULT '' NOT NULL,
	publisher varchar(45) DEFAULT '' NOT NULL,
	source varchar(255) DEFAULT '' NOT NULL,
	location_country varchar(45) DEFAULT '' NOT NULL,
	location_region varchar(45) DEFAULT '' NOT NULL,
	location_city varchar(45) DEFAULT '' NOT NULL,
	latitude decimal(24,14) DEFAULT '0.00000000000000' NOT NULL,
	longitude decimal(24,14) DEFAULT '0.00000000000000' NOT NULL,
	ranking int(11) unsigned DEFAULT '0',
	content_creation_date int(11) unsigned DEFAULT '0',
	content_modification_date int(11) unsigned DEFAULT '0',
	note text NOT NULL,

	# px,mm,cm,m,p, ...
	unit char(3) DEFAULT '' NOT NULL,

	# AUDIO + VIDEO
	duration float unsigned DEFAULT '0' NOT NULL,

	# RGB,sRGB,YUV, ...
	color_space varchar(4) DEFAULT '' NOT NULL,

	# TEXT ASSET
	# text document include x pages
	pages int(4) unsigned DEFAULT '0' NOT NULL,

	# TEXT + AUDIO + VIDEO
	# correspond to the language of the document
	language varchar(12) DEFAULT '' NOT NULL,

	# FE permissions
	fe_groups tinytext NOT NULL,
);