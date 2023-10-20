CREATE TABLE tx_testirremm_hotel
(
	title tinytext NOT NULL,
);

CREATE TABLE tx_testirremm_offer
(
	title tinytext NOT NULL,
);

CREATE TABLE tx_testirremm_price
(
	title tinytext NOT NULL,
	price varchar(255) DEFAULT '0.00' NOT NULL,
);
