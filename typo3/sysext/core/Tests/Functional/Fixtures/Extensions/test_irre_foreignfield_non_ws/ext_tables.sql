CREATE TABLE tx_testirreforeignfieldnonws_hotel
(
  parentid int(11) DEFAULT '0' NOT NULL,
  parenttable tinytext NOT NULL,
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  offers int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirreforeignfieldnonws_offer
(
  parentid int(11) DEFAULT '0' NOT NULL,
  parenttable tinytext NOT NULL,
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  prices int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirreforeignfieldnonws_price
(
  parentid int(11) DEFAULT '0' NOT NULL,
  parenttable tinytext NOT NULL,
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  price varchar(255) DEFAULT '0.00' NOT NULL
);

CREATE TABLE pages
(
  tx_testirreforeignfieldnonws_hotels int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tt_content
(
  tx_testirreforeignfieldnonws_hotels int(11) DEFAULT '0' NOT NULL,
  tx_testirreforeignfieldnonws_flexform mediumtext
);
