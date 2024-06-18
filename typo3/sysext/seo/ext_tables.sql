CREATE TABLE pages (
	# @todo: db analyzer makes this varchar which would be ok, but the default is lost. needs review
	sitemap_priority decimal(2,1) DEFAULT '0.5' NOT NULL,
);
