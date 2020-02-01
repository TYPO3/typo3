#
# Table structure for table 'be_dashboards'
#
CREATE TABLE be_dashboards (
    identifier varchar(120) DEFAULT '' NOT NULL,
    title varchar(120) DEFAULT '' NOT NULL,
    widgets text
);

