# TYPO3 Extension Manager dump 1.0
#
# Host: TYPO3_host    Database: t3_testsite
#--------------------------------------------------------


#
# Table structure for table 'tx_impexp_presets'
#
CREATE TABLE tx_impexp_presets (
  uid int(11) NOT NULL auto_increment,
  user_uid int(11) DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  public tinyint(3) DEFAULT '0' NOT NULL,
  item_uid int(11) DEFAULT '0' NOT NULL,
  preset_data blob NOT NULL,
  PRIMARY KEY (uid),
  KEY lookup (item_uid)
);
