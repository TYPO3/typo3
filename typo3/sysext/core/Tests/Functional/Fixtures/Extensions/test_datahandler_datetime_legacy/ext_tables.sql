#
# Table structure for table 'tx_testdatahandler_datetime_legacy' for testing legacy datetime fields
#
CREATE TABLE tx_testdatahandler_datetime_legacy (
	title VARCHAR(255) DEFAULT '' NOT NULL,
	datetime_native_notnull datetime NOT NULL,
	date_native_notnull date NOT NULL,
	timesec_native_notnull time NOT NULL,
	time_native_notnull time NOT NULL,
	comment VARCHAR(255) DEFAULT '' NOT NULL
);
