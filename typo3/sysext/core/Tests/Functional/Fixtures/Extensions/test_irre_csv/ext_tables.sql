####################################################
# 1:n relations using comma separated values as list
####################################################

#
# Table structure for table 'tx_testirrecsv_hotel'
#
CREATE TABLE tx_testirrecsv_hotel
(
    title  tinytext NOT NULL,
    offers text     NOT NULL
);

#
# Table structure for table 'tx_testirrecsv_offer'
#
CREATE TABLE tx_testirrecsv_offer
(
    title  tinytext NOT NULL,
    prices text     NOT NULL
);

#
# Table structure for table 'tx_testirrecsv_price'
#
CREATE TABLE tx_testirrecsv_price
(
    title tinytext                    NOT NULL,
    price varchar(255) DEFAULT '0.00' NOT NULL
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages
(
    tx_testirrecsv_hotels text
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content
(
    tx_testirrecsv_hotels text
);
