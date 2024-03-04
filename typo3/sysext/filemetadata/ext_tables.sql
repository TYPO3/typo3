CREATE TABLE sys_file_metadata (
	# @todo: status is odd. It should be an int field, but can not since it is often created as empty string. default should be 1 "ok".
	status varchar(24) DEFAULT '',
	latitude decimal(24,14) DEFAULT '0.00000000000000',
	longitude decimal(24,14) DEFAULT '0.00000000000000',

	# TEXT ASSET
	# text document include x pages
	pages int(4) unsigned DEFAULT '0',
);
