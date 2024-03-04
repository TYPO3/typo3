CREATE TABLE tx_blogexample_domain_model_person (
	# type=passthrough needs manual configuration
	salutation varchar(4) DEFAULT '' NOT NULL,
);

# @deprecated Can be removed as soon as int / native type is enforced
CREATE TABLE tx_blogexample_domain_model_dateexample (
	datetime_text varchar(255) DEFAULT '' NOT NULL,
);

# @deprecated Can be removed as soon as int / native type is enforced
CREATE TABLE tx_blogexample_domain_model_datetimeimmutableexample (
	datetime_immutable_text varchar(255) DEFAULT '' NOT NULL,
);
