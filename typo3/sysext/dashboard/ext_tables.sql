CREATE TABLE be_dashboards (
    # type=passthrough needs manual configuration
    cruser_id int(11) unsigned DEFAULT 0 NOT NULL,
    # No TCA column
    widgets text,
    KEY identifier (identifier)
);
