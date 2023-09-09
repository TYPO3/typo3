#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	seo_title varchar(255) DEFAULT '' NOT NULL,
	og_title varchar(255) DEFAULT '' NOT NULL,
	og_description text,
	twitter_title varchar(255) DEFAULT '' NOT NULL,
	twitter_description text,
	twitter_card varchar(255) DEFAULT '' NOT NULL,
	canonical_link varchar(2048) DEFAULT '' NOT NULL,
	sitemap_priority decimal(2,1) DEFAULT '0.5' NOT NULL,
	sitemap_changefreq varchar(10) DEFAULT '' NOT NULL,
);
