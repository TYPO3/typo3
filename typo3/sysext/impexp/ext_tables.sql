
#
# Table structure for table 'tx_impexp_presets'
#
CREATE TABLE tx_impexp_presets (
  uid int(11) NOT NULL auto_increment,
  user_uid int(11) DEFAULT '0' NOT NULL,
  title varchar(255) DEFAULT '' NOT NULL,
  public tinyint(3) DEFAULT '0' NOT NULL,
  item_uid int(11) DEFAULT '0' NOT NULL,
  preset_data blob,
  PRIMARY KEY (uid),
  KEY lookup (item_uid)
);
