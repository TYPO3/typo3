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

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'pages_language_overlay'
#
CREATE TABLE pages_language_overlay (
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'sys_template'
#
CREATE TABLE sys_template (
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);