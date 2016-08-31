#
# Add field 'tx_extbase_type' to table 'fe_users'
#
CREATE TABLE fe_users (
	tx_extbase_type varchar(255) DEFAULT '0' NOT NULL
);

#
# Add field 'tx_extbase_type' to table 'fe_groups'
#
CREATE TABLE fe_groups (
	tx_extbase_type varchar(255) DEFAULT '0' NOT NULL
);
