#
# Table structure for table "tx_extensionmanager_domain_model_repository"
#
DROP TABLE IF EXISTS tx_extensionmanager_domain_model_repository;
CREATE TABLE tx_extensionmanager_domain_model_repository (
  uid int(11) unsigned NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  title varchar(150) NOT NULL default '',
  description mediumtext,
  wsdl_url varchar(100) NOT NULL default '',
  mirror_list_url varchar(100) NOT NULL default '',
  last_update int(11) unsigned DEFAULT '0' NOT NULL,
  extension_count int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid)
);

INSERT INTO tx_extensionmanager_domain_model_repository (title, description, wsdl_url, mirror_list_url, last_update) VALUES ('TYPO3.org Main Repository', 'Main repository on typo3.org. This repository has some mirrors configured which are available with the mirror url.', 'https://typo3.org/wsdl/tx_ter_wsdl.php', 'https://repositories.typo3.org/mirrors.xml.gz', '1346191200');
