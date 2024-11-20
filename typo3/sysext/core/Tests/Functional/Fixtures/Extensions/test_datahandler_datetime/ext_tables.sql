#
# Table structure for table 'tx_testdatahandler_datetime' for testing datetime fields
#
CREATE TABLE tx_testdatahandler_datetime (
	title VARCHAR(255) DEFAULT '' NOT NULL,
	datetime_int bigint(20) NOT NULL DEFAULT 0,
	datetime_int_nullable bigint(20) DEFAULT NULL,
	datetime_native datetime DEFAULT NULL,
	date_int bigint(20) NOT NULL DEFAULT 0,
	date_int_nullable bigint(20) DEFAULT NULL,
	date_native date DEFAULT NULL,
	timesec_int bigint(20) NOT NULL DEFAULT 0,
	timesec_int_nullable bigint(20) DEFAULT NULL,
	timesec_native time DEFAULT NULL,
	time_int bigint(20) NOT NULL DEFAULT 0,
	time_int_nullable bigint(20) DEFAULT NULL,
	time_native time DEFAULT NULL,
	comment VARCHAR(255) DEFAULT '' NOT NULL
);
