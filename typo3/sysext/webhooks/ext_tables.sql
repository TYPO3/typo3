CREATE TABLE sys_webhook (
	UNIQUE identifier_key (identifier),
	KEY index_source (webhook_type(5))
);
