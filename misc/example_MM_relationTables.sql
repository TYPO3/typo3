
#
# Example MM table for database relations
#

CREATE TABLE example_db_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
) TYPE=MyISAM;

#
# Example MM table for file attachments
#

CREATE TABLE example_files_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign varchar(60) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
) TYPE=MyISAM;

