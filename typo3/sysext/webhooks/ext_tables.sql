#
# Table structure for table 'sys_webhook'
#
CREATE TABLE sys_webhook (
	name varchar(100) DEFAULT '' NOT NULL,
	url varchar(2048) DEFAULT '' NOT NULL,
	method varchar(10) DEFAULT '' NOT NULL,
	secret varchar(255) DEFAULT '' NOT NULL,
	webhook_type varchar(255) DEFAULT '' NOT NULL,
	verify_ssl int(1) DEFAULT 1 NOT NULL,

	UNIQUE identifier_key (identifier),
	KEY index_source (webhook_type(5))
);
