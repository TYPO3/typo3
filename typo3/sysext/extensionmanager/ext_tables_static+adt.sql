#
# Table structure for table "tx_extensionmanager_domain_model_repository"
#
DROP TABLE IF EXISTS tx_extensionmanager_domain_model_repository;
CREATE TABLE tx_extensionmanager_domain_model_repository (
  uid int(11) unsigned NOT NULL auto_increment,
  title varchar(150) NOT NULL default '',
  description mediumtext,
  wsdl_url varchar(100) NOT NULL default '',
  mirror_url varchar(100) NOT NULL default '',
  lastUpdated int(11) unsigned NOT NULL default '0',
  extCount int(11) NOT NULL default '0',
  PRIMARY KEY (uid)
);


INSERT INTO tx_extensionmanager_domain_model_repository VALUES ('1', 'TYPO3.org Main Repository', 'Main repository on typo3.org. This repository has some mirrors configured which are available with the mirror url.', 'http://typo3.org/wsdl/tx_ter_wsdl.php', 'http://repositories.typo3.org/mirrors.xml.gz', '0', '0');


