# 1nff: 1:n relations using foreign_field as pointer on child table

CREATE TABLE tx_testirreforeignfield_hotel
(
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
);

CREATE TABLE tx_testirreforeignfield_offer
(
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
);

CREATE TABLE tx_testirreforeignfield_price
(
  parentidentifier tinytext NOT NULL,
  title tinytext NOT NULL,
  price varchar(255) DEFAULT '0.00' NOT NULL
);
