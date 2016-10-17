#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata (
	visible int(11) unsigned DEFAULT '1',
	status varchar(24) DEFAULT '',
	keywords text,
	caption text,
	creator_tool varchar(255) DEFAULT '',
	download_name varchar(255) DEFAULT '',
	creator varchar(255) DEFAULT '',
	publisher varchar(45) DEFAULT '',
	source varchar(255) DEFAULT '',
	copyright text,
	location_country varchar(45) DEFAULT '',
	location_region varchar(45) DEFAULT '',
	location_city varchar(45) DEFAULT '',
	latitude decimal(24,14) DEFAULT '0.00000000000000',
	longitude decimal(24,14) DEFAULT '0.00000000000000',
	ranking int(11) unsigned DEFAULT '0',
	content_creation_date int(11) unsigned DEFAULT '0',
	content_modification_date int(11) unsigned DEFAULT '0',
	note text,

	# px,mm,cm,m,p, ...
	unit char(3) DEFAULT '',

	# AUDIO + VIDEO
	duration float unsigned DEFAULT '0',

	# RGB,sRGB,YUV, ...
	color_space varchar(4) DEFAULT '',

	# TEXT ASSET
	# text document include x pages
	pages int(4) unsigned DEFAULT '0',

	# TEXT + AUDIO + VIDEO
	# correspond to the language of the document
	language varchar(12) DEFAULT '',

	# FE permissions
	fe_groups tinytext
);