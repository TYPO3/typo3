# Keeping most fields here since that table *should not* have TCA at all
CREATE TABLE tx_extensionmanager_domain_model_extension (
  extension_key varchar(60) NOT NULL default '',
  remote varchar(100) NOT NULL default 'ter',
  version varchar(15) NOT NULL default '',
  alldownloadcounter int(11) unsigned NOT NULL default '0',
  downloadcounter int(11) unsigned NOT NULL default '0',
  title varchar(150) NOT NULL default '',
  serialized_dependencies mediumtext,
  author_name varchar(255) NOT NULL default '',
  author_email varchar(255) NOT NULL default '',
  ownerusername varchar(50) NOT NULL default '',
  md5hash varchar(35) NOT NULL default '',
  authorcompany varchar(255) NOT NULL default '',
  integer_version int(11) NOT NULL default '0',
  lastreviewedversion int(3) NOT NULL default '0',
  documentation_link varchar(2048),
  distribution_image varchar(255),
  distribution_welcome_image varchar(255),

  KEY index_extrepo (extension_key,remote),
  KEY index_versionrepo (integer_version,remote,extension_key),
  KEY index_currentversions (current_version,review_state),
  UNIQUE versionextrepo (extension_key,version,remote)
);
