CREATE TABLE `tx_test_package_initialization` (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);
