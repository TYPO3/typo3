CREATE TABLE tca_select_items (
	rowField VARCHAR(255) DEFAULT '' NOT NULL,
	rowFieldTwo VARCHAR(255) DEFAULT '' NOT NULL,
	mm_field int(11) DEFAULT '0' NOT NULL,
	foreign_field VARCHAR(255) DEFAULT '' NOT NULL,
);

CREATE TABLE foreign_table (
	title VARCHAR(255) DEFAULT '' NOT NULL,
	groupingfield1 VARCHAR(255) DEFAULT '' NOT NULL,
	groupingfield2 VARCHAR(255) DEFAULT '' NOT NULL,
	fal_field int(11) DEFAULT '0' NOT NULL,
);
