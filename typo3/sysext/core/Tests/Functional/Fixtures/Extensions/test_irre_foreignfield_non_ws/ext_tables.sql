CREATE TABLE tx_testirreforeignfieldnonws_hotel
(
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
);

CREATE TABLE tx_testirreforeignfieldnonws_offer
(
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
);

CREATE TABLE tx_testirreforeignfieldnonws_price
(
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  price varchar(255) DEFAULT '0.00' NOT NULL
);
