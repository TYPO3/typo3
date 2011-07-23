CREATE TABLE ###CACHE_MM_TABLE### (
	id_cache int(11) unsigned NOT NULL,
	id_tags int(11) unsigned NOT NULL,
	KEY cache_id (id_cache),
	KEY tag_id (id_tags)
) ENGINE=InnoDB;
