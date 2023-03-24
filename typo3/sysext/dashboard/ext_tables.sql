#
# Table structure for table 'be_dashboards'
#
CREATE TABLE be_dashboards (
    identifier varchar(120) DEFAULT '' NOT NULL,
    cruser_id int(11) unsigned DEFAULT 0 NOT NULL,
    title varchar(120) DEFAULT '' NOT NULL,
    widgets text,
    KEY identifier (identifier)
);

