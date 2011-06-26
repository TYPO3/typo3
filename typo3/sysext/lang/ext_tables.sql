#
# Table structure for table 'tx_lang_l10n_cache'
#
CREATE TABLE tx_lang_l10n_cache (
    id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier varchar(250) DEFAULT '' NOT NULL,
    crdate int(11) UNSIGNED DEFAULT '0' NOT NULL,
    content mediumblob,
    lifetime int(11) UNSIGNED DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier)
) ENGINE=InnoDB;
 
#
# Table structure for table 'tx_lang_l10n_cache_tags'
#
CREATE TABLE tx_lang_l10n_cache_tags (
    id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier varchar(250) DEFAULT '' NOT NULL,
    tag varchar(250) DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier),
    KEY cache_tag (tag)
) ENGINE=InnoDB;