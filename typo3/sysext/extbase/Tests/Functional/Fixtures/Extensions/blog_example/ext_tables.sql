# Table for NoTcaEntity - intentionally has no TCA definition to test history tracker fallback
CREATE TABLE tx_blogexample_domain_model_notcaentity (
	uid int(11) NOT NULL AUTO_INCREMENT,
	pid int(11) DEFAULT 0 NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_blogexample_domain_model_person (
	# type=passthrough needs manual configuration
	salutation varchar(4) DEFAULT '' NOT NULL,
	age_group int(11) DEFAULT NULL,
	age int(11) DEFAULT NULL,
);

# @deprecated Can be removed as soon as int / native type is enforced
CREATE TABLE tx_blogexample_domain_model_dateexample (
	datetime_text varchar(255) DEFAULT '' NOT NULL,
);

# @deprecated Can be removed as soon as int / native type is enforced
CREATE TABLE tx_blogexample_domain_model_datetimeimmutableexample (
	datetime_immutable_text varchar(255) DEFAULT '' NOT NULL,
);
