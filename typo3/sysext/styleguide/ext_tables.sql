CREATE TABLE tx_styleguide_forms_staticdata (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	value_1 tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE tx_styleguide_forms (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(255) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	type_field text NOT NULL,

	input_1 text NOT NULL,
	input_2 text NOT NULL,
	input_3 text NOT NULL,
	input_4 text NOT NULL,
	input_5 text NOT NULL,
	input_6 text NOT NULL,
	input_7 text NOT NULL,
	input_8 text NOT NULL,
	input_9 text NOT NULL,
	input_10 text NOT NULL,
	input_11 text NOT NULL,
	input_12 text NOT NULL,
	input_13 text NOT NULL,
	input_14 text,
	input_15 text NOT NULL,
	input_16 text NOT NULL,
	input_18 text NOT NULL,
	input_19 text NOT NULL,
	input_20 text NOT NULL,
	input_21 text NOT NULL,
	input_22 text NOT NULL,
	input_23 text NOT NULL,
	input_24 text NOT NULL,
	input_25 text NOT NULL,
	input_26 text NOT NULL,
	input_27 text NOT NULL,
	input_28 text NOT NULL,
	input_29 text,
	input_30 text NOT NULL,
	input_31 text NOT NULL,
	input_32 text NOT NULL,
	input_33 text NOT NULL,
	input_34 text NOT NULL,
	input_36 text NOT NULL,

	text_1 text,
	text_2 text,
	text_3 text,
	text_4 text,
	text_5 text,
	text_6 text,
	text_8 text,
	text_9 text,
	text_10 text,
	text_11 text,
	text_12 text,
	text_13 text,
	text_14 text,
	text_15 text,

	checkbox_1 int(11) DEFAULT '0' NOT NULL,
	checkbox_2 int(11) DEFAULT '0' NOT NULL,
	checkbox_3 int(11) DEFAULT '0' NOT NULL,
	checkbox_4 int(11) DEFAULT '0' NOT NULL,
	checkbox_5 int(11) DEFAULT '0' NOT NULL,
	checkbox_6 int(11) DEFAULT '0' NOT NULL,
	checkbox_7 int(11) DEFAULT '0' NOT NULL,
	checkbox_8 int(11) DEFAULT '0' NOT NULL,
	checkbox_9 int(11) DEFAULT '0' NOT NULL,
	checkbox_10 int(11) DEFAULT '0' NOT NULL,
	checkbox_11 int(11) DEFAULT '0' NOT NULL,
	checkbox_12 int(11) DEFAULT '0' NOT NULL,
	checkbox_13 int(11) DEFAULT '0' NOT NULL,
	checkbox_14 int(11) DEFAULT '0' NOT NULL,
	checkbox_15 int(11) DEFAULT '0' NOT NULL,
	checkbox_16 int(11) DEFAULT '0' NOT NULL,
	checkbox_17 int(11) DEFAULT '0' NOT NULL,
	checkbox_18 int(11) DEFAULT '0' NOT NULL,

	radio_1 int(11) DEFAULT '0' NOT NULL,
	radio_2 int(11) DEFAULT '0' NOT NULL,
	radio_3 int(11) DEFAULT '0' NOT NULL,
	radio_4 text NOT NULL,
	radio_5 int(11) DEFAULT '0' NOT NULL,
	radio_6 int(11) DEFAULT '0' NOT NULL,

	select_1 text NOT NULL,
	select_2 text NOT NULL,
	select_3 text NOT NULL,
	select_4 text NOT NULL,
	select_5 text NOT NULL,
	select_6 text NOT NULL,
	select_7 text NOT NULL,
	select_8 text NOT NULL,
	select_9 text NOT NULL,
	select_10 text NOT NULL,
	select_11 text NOT NULL,
	select_12 text NOT NULL,
	select_13 text NOT NULL,
	select_14 text NOT NULL,
	select_15 text NOT NULL,
	select_16 text NOT NULL,
	select_17 text NOT NULL,
	select_21 text NOT NULL,
	select_22 text NOT NULL,
	select_23 text NOT NULL,
	select_24 text NOT NULL,
	select_25 text NOT NULL,
	select_26 text NOT NULL,
	select_27 text NOT NULL,
	select_28 text NOT NULL,
	select_29 text NOT NULL,
	select_30 text NOT NULL,
	select_31 text NOT NULL,
	select_32 text NOT NULL,
	select_33 text NOT NULL,

	group_1 text NOT NULL,
	group_2 text NOT NULL,
	group_3 text NOT NULL,
	group_4 text NOT NULL,
	group_5 text NOT NULL,
	group_6 text NOT NULL,
	group_7 text NOT NULL,
	group_8 text NOT NULL,
	group_9 text NOT NULL,
	group_10 text NOT NULL,
	group_11 text NOT NULL,
	group_12 text NOT NULL,

	none_1 text NOT NULL,
	none_2 text NOT NULL,
	none_3 text NOT NULL,
	none_4 text NOT NULL,
	none_5 text NOT NULL,
	none_6 text NOT NULL,

	passthrough_1 text NOT NULL,

	user_1 text,
	user_2 text,

	flex_1 text,
	flex_2 text,
	flex_3 text,
	flex_4 text,
	flex_5 text,

	inline_1 int(11) DEFAULT '0' NOT NULL,
	inline_2 int(11) DEFAULT '0' NOT NULL,
	inline_3 int(11) DEFAULT '0' NOT NULL,

	palette_1_1 int(11) DEFAULT '0' NOT NULL,
	palette_1_2 text,
	palette_1_3 int(11) DEFAULT '0' NOT NULL,
	palette_2_1 text NOT NULL,
	palette_3_1 text NOT NULL,
	palette_3_2 text NOT NULL,
	palette_4_1 text NOT NULL,
	palette_4_2 text NOT NULL,
	palette_4_3 text NOT NULL,
	palette_4_4 text NOT NULL,
	palette_5_1 text NOT NULL,
	palette_5_2 text NOT NULL,
	palette_6_1 text NOT NULL,
	palette_6_2 text NOT NULL,
	palette_6_3 text NOT NULL,

	wizard_1 text NOT NULL,
	wizard_2 text NOT NULL,
	wizard_3 text NOT NULL,
	wizard_4 text NOT NULL,
	wizard_5 text NOT NULL,
	wizard_6 text NOT NULL,
	wizard_7 text NOT NULL,

	rte_1 text,
	rte_2 text,
	rte_3 text,
	rte_4 text,

	t3editor_1 text,
	t3editor_2 text,
	t3editor_5 text,
	t3editor_6 text,

	system_1 text NOT NULL,
	system_2 text NOT NULL,
	system_3 text NOT NULL,
	system_4 text NOT NULL,
	system_5 text NOT NULL,
	system_6 text NOT NULL,
	system_7 text NOT NULL,
	system_8 text NOT NULL,
	system_9 text NOT NULL,
	system_10 text NOT NULL,
	system_11 text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE tx_styleguide_required (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(255) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	notrequired_1 text,

	input_1 text NOT NULL,
	input_2 text NOT NULL,
	input_3 text NOT NULL,
	input_4 text NOT NULL,
	input_5 text NOT NULL,

	text_1 text,

	select_1 text,
	select_2 text,
	select_3 text,
	select_4 text,

	group_1 text,
	group_2 text,

	rte_1 text,
	rte_2 text,

	inline_1 text,
	inline_2 text,
	inline_3 text,

	flex_1 text,
	flex_2 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE tx_styleguide_inlineexpand (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(255) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	inline_1 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


CREATE TABLE tx_styleguide_forms_inline_2_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	input_1 text NOT NULL,
	inline_1 int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_inline_2_child2 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	input_1 text NOT NULL,
	text_2 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_inline_3_mm (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	select_parent int(11) unsigned DEFAULT '0' NOT NULL,
	select_child int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_inline_3_child (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	input_1 varchar(255) DEFAULT '' NOT NULL,
	select_child int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_required_flex_2_inline_1_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	input_1 text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_required_rte_2_inline_1_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	text_1 text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_required_inline_1_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	input_1 text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_inlineexpand_inline_1_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,

	rte_1 text NOT NULL,
	tree_1 text NOT NULL,
	fal_1 int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_required_inline_2_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	input_1 text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_required_inline_3_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,
	input_1 text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_rte_3_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,

	text_1 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_rte_4_flex_inline_1_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,

	text_1 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_t3editor_5_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,

	t3editor_1 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_styleguide_forms_t3editor_6_flex_inline_1_child1 (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	parentid int(11) DEFAULT '0' NOT NULL,
	parenttable text NOT NULL,
	parentidentifier text NOT NULL,

	t3editor_1 text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
