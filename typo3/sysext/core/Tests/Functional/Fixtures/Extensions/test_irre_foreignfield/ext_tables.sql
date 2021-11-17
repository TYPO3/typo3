# 1nff: 1:n relations using foreign_field as pointer on child table

CREATE TABLE tx_testirreforeignfield_hotel
(
  parentid int(11) DEFAULT '0' NOT NULL,
  parenttable tinytext NOT NULL,
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  offers int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirreforeignfield_offer
(
  parentid int(11) DEFAULT '0' NOT NULL,
  parenttable tinytext NOT NULL,
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  prices int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testirreforeignfield_price
(
  parentid int(11) DEFAULT '0' NOT NULL,
  parenttable tinytext NOT NULL,
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  price varchar(255) DEFAULT '0.00' NOT NULL
);

#
# Extend the pages table to have hotels with a 1:n relationship added there
#
CREATE TABLE pages
(
  tx_testirreforeignfield_hotels int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tt_content
(
  tx_testirreforeignfield_hotels int(11) DEFAULT '0' NOT NULL,
  tx_testirreforeignfield_flexform mediumtext
);
