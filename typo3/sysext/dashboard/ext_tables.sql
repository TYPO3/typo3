CREATE TABLE be_dashboards (
    # type=passthrough needs manual configuration
    cruser_id int(11) unsigned DEFAULT 0 NOT NULL,
    # No TCA column
    widgets text,
    UNIQUE identifier (identifier),
    KEY owner (cruser_id)
);
