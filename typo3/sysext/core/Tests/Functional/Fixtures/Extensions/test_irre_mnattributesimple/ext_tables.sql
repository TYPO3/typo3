CREATE TABLE tx_testirremnattributesimple_hotel
(
	title tinytext NOT NULL,
);

CREATE TABLE tx_testirremnattributesimple_hotel_offer_rel
(
	hotelsort int(10) DEFAULT '0' NOT NULL,
	offersort int(10) DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_testirremnattributesimple_offer
(
	title tinytext NOT NULL,
);
