CREATE TABLE pages (
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tt_content (
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_groups (
    tx_styleguide_isdemorecord tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE be_users (
    tx_styleguide_isdemorecord tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE fe_groups (
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);


CREATE TABLE tx_styleguide_ctrl_common (
    title text,
);


CREATE TABLE tx_styleguide_ctrl_minimal (
    title text,
);


CREATE TABLE tx_styleguide_displaycond (
    input_1 text,
    input_2 text,
    input_4 text,
    input_5 text,
    input_6 text,
    input_7 text,
    input_8 text,
    input_9 text,
    input_10 text,
    input_11 text,
    input_12 text,
    input_13 text,
    input_16 text,
    input_17 text,
    input_18 text,
    input_19 text,
    input_20 text,

    select_1 text,
    select_2 text,
    select_3 text,
    select_4 text,
);


CREATE TABLE tx_styleguide_elements_basic (
    input_1 text,
    input_2 text,
    input_3 text,
    input_4 text,
    input_5 text,
    input_10 text,
    input_11 text,
    input_12 text,
    input_13 text,
    input_14 text,
    input_15 text,
    input_19 text,
    input_21 text,
    input_22 text,
    input_23 text,
    input_24 text,
    input_26 text,
    input_27 text,
    input_28 text,
    input_33 text,
    input_35 text,
    input_36 text,
    input_40 text,
    input_41 text,
    input_42 text,
    input_43 text,

    text_12 text,

    none_1 text,
    none_2 text,
    none_3 text,

    passthrough_1 text,
    passthrough_2 text,

    user_1 text,
    user_2 text,

    unknown_1 text,
);


CREATE TABLE tx_styleguide_elements_rte (
    input_palette_1 text,
    rte_palette_1 text
);

CREATE TABLE tx_styleguide_elements_slugs (
    input_1 text,
    input_2 text,
    input_3 text,
);


CREATE TABLE tx_styleguide_elements_rte_flex_1_inline_1_child (
    parentid int(11) DEFAULT '0' NOT NULL,
    parenttable text,
);

CREATE TABLE tx_styleguide_elements_select (
    select_single_1 text,
    select_single_2 text,
    select_single_3 text,
    select_single_4 text,
    select_single_5 text,
    select_single_7 text,
    select_single_8 text,
    select_single_10 text,
    select_single_11 text,
    select_single_12 text,
    select_single_13 text,
    select_single_14 text,
    select_single_15 text,
    select_single_16 text,
    select_single_17 text,
    select_single_18 text,
    select_single_19 text,
    select_single_20 text,

    select_singlebox_1 text,
    select_singlebox_2 text,
    select_singlebox_3 text,

    select_checkbox_1 text,
    select_checkbox_2 text,
    select_checkbox_3 text,
    select_checkbox_4 text,
    select_checkbox_5 text,
    select_checkbox_6 text,
    select_checkbox_7 text,

    select_multiplesidebyside_1 text,
    select_multiplesidebyside_2 text,
    select_multiplesidebyside_3 text,
    select_multiplesidebyside_5 text,
    select_multiplesidebyside_6 text,
    select_multiplesidebyside_7 text,
    select_multiplesidebyside_8 text,
    select_multiplesidebyside_9 text,
    select_multiplesidebyside_10 text,

    select_tree_1 text,
    select_tree_2 text,
    select_tree_3 text,
    select_tree_4 text,
    select_tree_5 text,
    select_tree_6 text,

    select_requestUpdate_1 text
);


# MM tables for fields defined in flex form data structures
# are NOT auto created by DefaultTcaSchema
CREATE TABLE tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm (
	uid_local int(11) unsigned DEFAULT 0 NOT NULL,
	uid_foreign int(11) unsigned DEFAULT 0 NOT NULL,
	sorting int(11) unsigned DEFAULT 0 NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT 0 NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);


CREATE TABLE tx_styleguide_elements_t3editor (
    t3editor_reload_1 int(11) DEFAULT '0' NOT NULL,
);


CREATE TABLE tx_styleguide_elements_t3editor_flex_1_inline_1_child (
    parentid int(11) DEFAULT '0' NOT NULL,
    parenttable text,
);

CREATE TABLE tx_styleguide_flex (
);


CREATE TABLE tx_styleguide_flex_flex_3_inline_1_child (
    parentid int(11) DEFAULT '0' NOT NULL,
    parenttable text,

    input_1 text
);

CREATE TABLE tx_styleguide_inline_11_child (
    input_1 text
);

CREATE TABLE tx_styleguide_inline_1n_inline_1_child (
    input_1 text,
    input_3 text,
    select_tree_1 text
);

CREATE TABLE tx_styleguide_inline_1n_inline_2_child (
    input_1 text,
    select_tree_1 text,
);

CREATE TABLE tx_styleguide_inline_1nreusabletable_child (
    role text
);

CREATE TABLE tx_styleguide_inline_1nnol10n_child (
    input_1 text
);

CREATE TABLE tx_styleguide_inline_1n1n_childchild (
    input_1 text
);

CREATE TABLE tx_styleguide_inline_expand_inline_1_child (
    dummy_1 text,
    select_tree_1 text,
);

CREATE TABLE tx_styleguide_inline_expandsingle_child (
    input_1 text
);

CREATE TABLE tx_styleguide_file (
);

CREATE TABLE tx_styleguide_inline_foreignrecorddefaults_child (
    input_1 text
);


CREATE TABLE tx_styleguide_inline_mm (
    title tinytext,
);


CREATE TABLE tx_styleguide_inline_mm_child (
    title tinytext,
);


CREATE TABLE tx_styleguide_inline_mm_childchild (
    title tinytext,
);


CREATE TABLE tx_styleguide_inline_mn (
    input_1 tinytext,
);


CREATE TABLE tx_styleguide_inline_mn_mm (
    parentid int(11) DEFAULT '0' NOT NULL,
    childid int(11) DEFAULT '0' NOT NULL,
    parentsort int(10) DEFAULT '0' NOT NULL,
    childsort int(10) DEFAULT '0' NOT NULL,
);


CREATE TABLE tx_styleguide_inline_mn_child (
    input_1 tinytext,
);


CREATE TABLE tx_styleguide_inline_mngroup (
    input_1 tinytext,
);


CREATE TABLE tx_styleguide_inline_mngroup_mm (
    parentsort int(10) DEFAULT '0' NOT NULL,
    childsort int(10) DEFAULT '0' NOT NULL,
);


CREATE TABLE tx_styleguide_inline_mngroup_child (
    input_1 tinytext,
);


CREATE TABLE tx_styleguide_inline_mnsymmetric (
    input_1 tinytext,
);


CREATE TABLE tx_styleguide_inline_mnsymmetric_mm (
    hotelid int(11) DEFAULT '0' NOT NULL,
    branchid int(11) DEFAULT '0' NOT NULL,
    hotelsort int(10) DEFAULT '0' NOT NULL,
    branchsort int(10) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_styleguide_inline_mnsymmetricgroup (
    input_1 tinytext,
);


CREATE TABLE tx_styleguide_inline_mnsymmetricgroup_mm (
    # int() kept for now, similar issue in core, needs further type=group works
    hotelid int(11) DEFAULT '0' NOT NULL,
    # int() kept for now, similar issue in core, needs further type=group works
    branchid int(11) DEFAULT '0' NOT NULL,
    hotelsort int(10) DEFAULT '0' NOT NULL,
    branchsort int(10) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_styleguide_inline_usecombination_mm (
    select_parent int(11) unsigned DEFAULT '0' NOT NULL,
    select_child int(11) unsigned DEFAULT '0' NOT NULL
);


CREATE TABLE tx_styleguide_inline_usecombination_child (
    input_1 varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tx_styleguide_inline_usecombinationbox_mm (
    select_parent int(11) unsigned DEFAULT '0' NOT NULL,
    select_child int(11) unsigned DEFAULT '0' NOT NULL
);


CREATE TABLE tx_styleguide_inline_usecombinationbox_child (
    input_1 varchar(255) DEFAULT '' NOT NULL
);


CREATE TABLE tx_styleguide_palette (
    palette_2_1 text,
    palette_3_1 text,
    palette_3_2 text,
    palette_4_1 text,
    palette_4_2 text,
    palette_4_3 text,
    palette_4_4 text,
    palette_5_1 text,
    palette_5_2 text,
    palette_6_1 text,
    palette_7_1 text
);


CREATE TABLE tx_styleguide_required (
    notrequired_1 text,

    input_1 text,

    select_1 text,
    select_2 text,
    select_3 text,
    select_4 text,
    select_5 text,

    rte_1 text,

    palette_input_1 text,
    palette_input_2 text
);


CREATE TABLE tx_styleguide_required_flex_2_inline_1_child (
    parentid int(11) DEFAULT '0' NOT NULL,
    parenttable text,

    input_1 text
);


CREATE TABLE tx_styleguide_required_inline_1_child (
    input_1 text
);


CREATE TABLE tx_styleguide_required_inline_2_child (
    input_1 text
);


CREATE TABLE tx_styleguide_required_inline_3_child (
    input_1 text
);

CREATE TABLE tx_styleguide_staticdata (
    value_1 tinytext
);


CREATE TABLE tx_styleguide_type (
    record_type text,
    input_1 text,
);


CREATE TABLE tx_styleguide_typeforeign (
    foreign_table int(11) DEFAULT '0' NOT NULL,
    input_1 text,
);


CREATE TABLE tx_styleguide_valuesdefault (
    input_1 text,

    select_1 text,
    select_2 text
);

CREATE TABLE tx_styleguide_l10nreadonly (
    input text,
    none text,
    language int(11) DEFAULT '0' NOT NULL,
    select_single text,
    select_single_box text,
    select_checkbox text,
    select_tree text,
    select_tree_mm text,
    select_multiplesidebyside text,
    select_multiplesidebyside_mm text,
);

CREATE TABLE tx_styleguide_l10nreadonly_inline_child (
    input text,
);

#
# Table structure for table 'tx_styleguide_inline_parentnosoftdelete'
#
CREATE TABLE tx_styleguide_inline_parentnosoftdelete (
    text_1 text
);
