CREATE TABLE tx_testselectflexmm_local (
	flex_1 text
);

# MM tables for fields defined in flex form data structures
# are NOT auto created by DefaultTcaSchema
CREATE TABLE tx_testselectflexmm_flex_1_multiplesidebyside_1_mm (
	uid_local int(11) unsigned DEFAULT 0 NOT NULL,
	uid_foreign int(11) unsigned DEFAULT 0 NOT NULL,
	sorting int(11) unsigned DEFAULT 0 NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT 0 NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

CREATE TABLE tx_testselectflexmm_foreign (
	title text
);
