CREATE TABLE pages (
    # type=passthrough needs manual configuration
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tt_content (
    # type=passthrough needs manual configuration
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE be_groups (
    # type=passthrough needs manual configuration
    tx_styleguide_isdemorecord tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE be_users (
    # type=passthrough needs manual configuration
    tx_styleguide_isdemorecord tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE fe_groups (
    # type=passthrough needs manual configuration
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    # type=passthrough needs manual configuration
    tx_styleguide_containsdemo varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tx_styleguide_elements_basic (
    # type=none needs manual configuration
    none_1 text,
    none_2 text,
    none_3 text,
    none_4 text,

    # type=passthrough needs manual configuration
    passthrough_1 text,
    passthrough_2 text,

    # type=user needs manual configuration
    user_1 text,
    user_2 text,
);

CREATE TABLE tx_styleguide_elements_select (
    # @todo This is a bug in tree DH handling with maxitems=1 TCA fields where
    #       DefaultTcaSchema currently creates a int default 0 not null field
    #       which fails with postgres when no page is selected.
    #       Build/Scripts/runTests.sh -s acceptance -d postgres -i 15 -p 8.3
    select_tree_7 varchar(255) DEFAULT '' NOT NULL
);

CREATE TABLE tx_styleguide_elements_rte_flex_1_inline_1_child (
    # type=passthrough needs manual configuration with inline flex parents
    parentid int(11) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration with inline flex parents
    parenttable text,
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

CREATE TABLE tx_styleguide_elements_t3editor_flex_1_inline_1_child (
    # type=passthrough needs manual configuration with inline flex parents
    parentid int(11) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration with inline flex parents
    parenttable text,
);

CREATE TABLE tx_styleguide_flex_flex_3_inline_1_child (
    # type=passthrough needs manual configuration with inline flex parents
    parentid int(11) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration with inline flex parents
    parenttable text,
);

CREATE TABLE tx_styleguide_inline_1nreusabletable_child (
    # type=passthrough needs manual configuration
    role text
);

CREATE TABLE tx_styleguide_inline_mn_mm (
    # type=passthrough needs manual configuration
    parentsort int(10) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration
    childsort int(10) DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_styleguide_inline_mngroup_mm (
    # type=passthrough needs manual configuration
    parentsort int(10) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration
    childsort int(10) DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_styleguide_inline_mnsymmetric_mm (
    # type=passthrough needs manual configuration
    hotelsort int(10) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration
    branchsort int(10) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_styleguide_inline_mnsymmetricgroup_mm (
    # int() kept for now, similar issue in core, needs further type=group works
    hotelid int(11) DEFAULT '0' NOT NULL,
    # int() kept for now, similar issue in core, needs further type=group works
    branchid int(11) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration
    hotelsort int(10) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration
    branchsort int(10) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_styleguide_required_flex_2_inline_1_child (
    # type=passthrough needs manual configuration with inline flex parents
    parentid int(11) DEFAULT '0' NOT NULL,
    # type=passthrough needs manual configuration with inline flex parents
    parenttable text,
);

CREATE TABLE tx_styleguide_l10nreadonly (
    # type=none needs manual configuration
    none text,
);
