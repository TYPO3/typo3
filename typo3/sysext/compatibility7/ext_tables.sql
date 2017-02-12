#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	menu_type varchar(30) DEFAULT '0' NOT NULL,
	select_key varchar(80) DEFAULT '' NOT NULL
);

#
# Additional fields for table 'pages'
#
CREATE TABLE pages (
	url_scheme tinyint(3) unsigned DEFAULT '0' NOT NULL
);
