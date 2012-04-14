#
# Table structure for table "sys_ter"
#
DROP TABLE IF EXISTS sys_ter;
CREATE TABLE sys_ter (
  uid int(11) unsigned NOT NULL auto_increment,
  title varchar(150) NOT NULL default '',
  description mediumtext,
  wsdl_url varchar(100) NOT NULL default '',
  mirror_url varchar(100) NOT NULL default '',
  lastUpdated int(11) unsigned NOT NULL default '0',
  extCount int(11) NOT NULL default '0',
  PRIMARY KEY (uid)
);


INSERT INTO sys_ter VALUES ('1', 'TYPO3.org Main Repository', 'Main repository on typo3.org. This repository has some mirrors configured which are available with the mirror url.', 'http://typo3.org/wsdl/tx_ter_wsdl.php', 'http://repositories.typo3.org/mirrors.xml.gz', '0', '0');


