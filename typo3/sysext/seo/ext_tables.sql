CREATE TABLE pages (
	# @todo: this should be text with default '' when implemented, see todo in DefaultTceSchema
	canonical_link varchar(2048) DEFAULT '' NOT NULL,
	# @todo: db analyzer makes this varchar which would be ok, but the default is lost. needs review
	sitemap_priority decimal(2,1) DEFAULT '0.5' NOT NULL,
);
