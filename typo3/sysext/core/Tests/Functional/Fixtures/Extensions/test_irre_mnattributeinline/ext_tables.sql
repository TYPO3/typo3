#######################################################################################################################
# mnasym: m:n bidirectional anti-symmetric relations using intermediate table
#######################################################################################################################

#
# Table structure for table 'tx_testirremnattributeinline_hotel'
#
CREATE TABLE tx_testirremnattributeinline_hotel
(
	title tinytext NOT NULL,
);



#
# Table structure for table 'tx_testirremnattributeinline_hotel_offer_rel'
#
CREATE TABLE tx_testirremnattributeinline_hotel_offer_rel
(
	hotelsort int(10) DEFAULT '0' NOT NULL,
	offersort int(10) DEFAULT '0' NOT NULL,
);



#
# Table structure for table 'tx_testirremnattributeinline_offer'
#
CREATE TABLE tx_testirremnattributeinline_offer
(
	title tinytext NOT NULL,
);



#
# Table structure for table 'tx_testirremnattributeinline_price'
#
CREATE TABLE tx_testirremnattributeinline_price
(
	title tinytext NOT NULL,
);
