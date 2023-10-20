#
# Table structure for table 'tx_parentchildtranslation_domain_model_main'
#
CREATE TABLE tx_parentchildtranslation_domain_model_main (
	title varchar(255) NOT NULL DEFAULT '',
	child int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Table structure for table 'tx_parentchildtranslation_domain_model_squeeze'
#
CREATE TABLE tx_parentchildtranslation_domain_model_squeeze (
	title varchar(255) NOT NULL DEFAULT '',
	child int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Table structure for table 'tx_parentchildtranslation_domain_model_child'
#
CREATE TABLE tx_parentchildtranslation_domain_model_child (
	title varchar(255) NOT NULL DEFAULT ''
);
