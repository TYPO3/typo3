#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	seo_title varchar(255) DEFAULT '' NOT NULL,
	og_title varchar(255) DEFAULT '' NOT NULL,
	twitter_title varchar(255) DEFAULT '' NOT NULL,
	# @todo: db analyzer makes this varchar which would be ok, but the default is lost. needs review
	sitemap_priority decimal(2,1) DEFAULT '0.5' NOT NULL,
);
