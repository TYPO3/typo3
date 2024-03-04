CREATE TABLE tx_impexp_presets (
  # type=passthrough fields need manual configuration
  title varchar(255) DEFAULT '' NOT NULL,
  public tinyint(3) DEFAULT '0' NOT NULL,
  item_uid int(11) DEFAULT '0' NOT NULL,
  user_uid int(11) unsigned DEFAULT '0' NOT NULL,
  preset_data blob,
  KEY lookup (item_uid)
);

# Some fields need manual configuration
CREATE TABLE tt_content (
  # type=passthrough fields need manual configuration
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);

# Some fields need manual configuration
CREATE TABLE pages (
  # type=passthrough fields need manual configuration
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);

# Some fields need manual configuration
CREATE TABLE sys_template (
  # type=passthrough fields need manual configuration
  tx_impexp_origuid int(11) DEFAULT '0' NOT NULL
);
