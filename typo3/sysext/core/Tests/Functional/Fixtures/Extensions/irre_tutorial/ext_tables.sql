#######################################################################################################################
# Extend the pages table to have hotels with a 1:n relationship added there
#######################################################################################################################

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
    tx_irretutorial_hotels int(11) DEFAULT '0' NOT NULL,
    tx_irretutorial_1ncsv_hotels text
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
    tx_irretutorial_1nff_hotels int(11) DEFAULT '0' NOT NULL,
    tx_irretutorial_1ncsv_hotels text,
    tx_irretutorial_flexform mediumtext
);


#######################################################################################################################
# 1ncsv: 1:n relations using comma separated values as list
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_1ncsv_hotel'
#
CREATE TABLE tx_irretutorial_1ncsv_hotel (
	title tinytext NOT NULL,
	offers text NOT NULL
);



#
# Table structure for table 'tx_irretutorial_1ncsv_offer'
#
CREATE TABLE tx_irretutorial_1ncsv_offer (
	title tinytext NOT NULL,
	prices text NOT NULL
);



#
# Table structure for table 'tx_irretutorial_1ncsv_price'
#
CREATE TABLE tx_irretutorial_1ncsv_price (
	title tinytext NOT NULL,
	price varchar(255) DEFAULT '0.00' NOT NULL
);

#######################################################################################################################
# 1nff: 1:n relations using foreign_field as pointer on child table
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_1nff_hotel'
#
CREATE TABLE tx_irretutorial_1nff_hotel (
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable tinytext NOT NULL,
	parentidentifier tinytext NOT NULL,
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_1nff_offer'
#
CREATE TABLE tx_irretutorial_1nff_offer (
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable tinytext NOT NULL,
	parentidentifier tinytext NOT NULL,
	title tinytext NOT NULL,
	prices int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_1nff_price'
#
CREATE TABLE tx_irretutorial_1nff_price (
	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable tinytext NOT NULL,
	parentidentifier tinytext NOT NULL,
	title tinytext NOT NULL,
	price varchar(255) DEFAULT '0.00' NOT NULL
);

#######################################################################################################################
# mnasym: m:n bidirectional anti-symmetric relations using intermediate table
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_mnasym_hotel'
#
CREATE TABLE tx_irretutorial_mnasym_hotel (
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnasym_hotel_offer_rel'
#
CREATE TABLE tx_irretutorial_mnasym_hotel_offer_rel (
	hotelid int(11) DEFAULT '0' NOT NULL,
	offerid int(11) DEFAULT '0' NOT NULL,
	hotelsort int(10) DEFAULT '0' NOT NULL,
	offersort int(10) DEFAULT '0' NOT NULL,
	prices int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnasym_offer'
#
CREATE TABLE tx_irretutorial_mnasym_offer (
	title tinytext NOT NULL,
	hotels int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnasym_price'
#
CREATE TABLE tx_irretutorial_mnasym_price (
	parentid int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	price varchar(255) DEFAULT '0.00' NOT NULL
);

#######################################################################################################################
# mnasym: m:n bidirectional anti-symmetric relations using regular MM tables
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_mnmmasym_hotel'
#
CREATE TABLE tx_irretutorial_mnmmasym_hotel (
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnmmasym_hotel_offer_rel'
#
CREATE TABLE tx_irretutorial_mnmmasym_hotel_offer_rel (
	uid int(11) NOT NULL auto_increment,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,
	ident varchar(255) DEFAULT '' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	PRIMARY KEY (uid)
);



#
# Table structure for table 'tx_irretutorial_mnmmasym_offer'
#
CREATE TABLE tx_irretutorial_mnmmasym_offer (
	title tinytext NOT NULL,
	hotels int(11) DEFAULT '0' NOT NULL,
	prices int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnmmasym_offer_price_rel'
#
CREATE TABLE tx_irretutorial_mnmmasym_offer_price_rel (
	uid int(11) NOT NULL auto_increment,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,
	ident varchar(255) DEFAULT '' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	PRIMARY KEY (uid)
);



#
# Table structure for table 'tx_irretutorial_mnmmasym_price'
#
CREATE TABLE tx_irretutorial_mnmmasym_price (
	title tinytext NOT NULL,
	price varchar(255) DEFAULT '0.00' NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);

#######################################################################################################################
# mnsym: m:n bidirectional symmetric relations using intermediate table
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_mnsym_hotel'
#
CREATE TABLE tx_irretutorial_mnsym_hotel (
	title tinytext NOT NULL,
	branches int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnsym_hotel_rel'
#
CREATE TABLE tx_irretutorial_mnsym_hotel_rel (
	hotelid int(11) DEFAULT '0' NOT NULL,
	branchid int(11) DEFAULT '0' NOT NULL,
	hotelsort int(10) DEFAULT '0' NOT NULL,
	branchsort int(10) DEFAULT '0' NOT NULL
);

#######################################################################################################################
# mnattr: m:n bidirectional (anti-)symmetric attributed relations using intermediate table
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_mnattr_hotel'
#
CREATE TABLE tx_irretutorial_mnattr_hotel (
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnattr_hotel_offer_rel'
#
CREATE TABLE tx_irretutorial_mnattr_hotel_offer_rel (
	hotelid int(11) DEFAULT '0' NOT NULL,
	offerid int(11) DEFAULT '0' NOT NULL,
	hotelsort int(10) DEFAULT '0' NOT NULL,
	offersort int(10) DEFAULT '0' NOT NULL,
	quality tinyint(4) DEFAULT '0' NOT NULL,
	allincl tinyint(4) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnattr_offer'
#
CREATE TABLE tx_irretutorial_mnattr_offer (
	title tinytext NOT NULL,
	hotels int(11) DEFAULT '0' NOT NULL
);
