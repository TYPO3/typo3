#######################################################################################################################
# mnasym: m:n bidirectional anti-symmetric relations using intermediate table
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_mnasym_hotel'
#
CREATE TABLE tx_irretutorial_mnasym_hotel
(
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnasym_hotel_offer_rel'
#
CREATE TABLE tx_irretutorial_mnasym_hotel_offer_rel
(
	hotelid int(11) DEFAULT '0' NOT NULL,
	offerid int(11) DEFAULT '0' NOT NULL,
	hotelsort int(10) DEFAULT '0' NOT NULL,
	offersort int(10) DEFAULT '0' NOT NULL,
	prices int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnasym_offer'
#
CREATE TABLE tx_irretutorial_mnasym_offer
(
	title tinytext NOT NULL,
	hotels int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnasym_price'
#
CREATE TABLE tx_irretutorial_mnasym_price
(
	parentid int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	price varchar(255) DEFAULT '0.00' NOT NULL
);


#######################################################################################################################
# mnattr: m:n bidirectional (anti-)symmetric attributed relations using intermediate table
#######################################################################################################################

#
# Table structure for table 'tx_irretutorial_mnattr_hotel'
#
CREATE TABLE tx_irretutorial_mnattr_hotel
(
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);



#
# Table structure for table 'tx_irretutorial_mnattr_hotel_offer_rel'
#
CREATE TABLE tx_irretutorial_mnattr_hotel_offer_rel
(
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
CREATE TABLE tx_irretutorial_mnattr_offer
(
	title tinytext NOT NULL,
	hotels int(11) DEFAULT '0' NOT NULL
);
