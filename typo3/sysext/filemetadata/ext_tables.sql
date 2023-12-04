#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata (
	# @todo: status is odd. It should be an int field, but can not since it is often created as empty string. default should be 1 "ok".
	status varchar(24) DEFAULT '',
	creator_tool varchar(255) DEFAULT '',
	download_name varchar(255) DEFAULT '',
	creator varchar(255) DEFAULT '',
	publisher varchar(45) DEFAULT '',
	source varchar(255) DEFAULT '',
	location_country varchar(45) DEFAULT '',
	location_region varchar(45) DEFAULT '',
	location_city varchar(45) DEFAULT '',
	latitude decimal(24,14) DEFAULT '0.00000000000000',
	longitude decimal(24,14) DEFAULT '0.00000000000000',

	# TEXT ASSET
	# text document include x pages
	pages int(4) unsigned DEFAULT '0',

	# TEXT + AUDIO + VIDEO
	# correspond to the language of the document
	language varchar(45) DEFAULT '',
);
