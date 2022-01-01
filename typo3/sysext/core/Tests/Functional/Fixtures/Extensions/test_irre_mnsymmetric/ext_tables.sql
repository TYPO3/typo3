CREATE TABLE tx_testirremnsymmetric_hotel
(
	title tinytext NOT NULL,
	branches int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirremnsymmetric_hotel_rel
(
	hotelid int(11) DEFAULT '0' NOT NULL,
	branchid int(11) DEFAULT '0' NOT NULL,
	hotelsort int(10) DEFAULT '0' NOT NULL,
	branchsort int(10) DEFAULT '0' NOT NULL
);
