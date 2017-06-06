#
# Table structure for table 'tx_expressionbuildertest'
#
CREATE TABLE tx_expressionbuildertest (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  aField text,
  aCsvField text,

  PRIMARY KEY (uid),
  KEY parent (pid)
);
