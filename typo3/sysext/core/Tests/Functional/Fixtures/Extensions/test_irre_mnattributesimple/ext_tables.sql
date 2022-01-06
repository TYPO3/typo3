CREATE TABLE tx_testirremnattributesimple_hotel
(
	title tinytext NOT NULL,
	offers int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirremnattributesimple_hotel_offer_rel
(
	hotelid int(11) DEFAULT '0' NOT NULL,
	offerid int(11) DEFAULT '0' NOT NULL,
	hotelsort int(10) DEFAULT '0' NOT NULL,
	offersort int(10) DEFAULT '0' NOT NULL,
	quality tinyint(4) DEFAULT '0' NOT NULL,
	allincl tinyint(4) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirremnattributesimple_offer
(
	title tinytext NOT NULL,
	hotels int(11) DEFAULT '0' NOT NULL
);
