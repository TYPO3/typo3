#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
	title varchar(50) DEFAULT '' NOT NULL,
	non_exclude_fields text,
	explicit_allowdeny text,
	allowed_languages varchar(255) DEFAULT '' NOT NULL,
	custom_options text,
	db_mountpoints text,
	pagetypes_select varchar(255) DEFAULT '' NOT NULL,
	tables_select text,
	tables_modify text,
	groupMods text,
	file_mountpoints text,
	file_permissions text,
	lockToDomain varchar(50) DEFAULT '' NOT NULL,
	TSconfig text,
	subgroup text,
	workspace_perms tinyint(3) DEFAULT '1' NOT NULL,
	category_perms text
);

#
# Table structure for table 'be_sessions'
#
CREATE TABLE be_sessions (
	ses_id varchar(32) DEFAULT '' NOT NULL,
	ses_iplock varchar(39) DEFAULT '' NOT NULL,
	ses_userid int(11) unsigned DEFAULT '0' NOT NULL,
	ses_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	ses_data longblob,
	ses_backuserid int(11) NOT NULL default '0',
	PRIMARY KEY (ses_id),
	KEY ses_tstamp (ses_tstamp)
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	username varchar(50) DEFAULT '' NOT NULL,
	avatar int(11) unsigned NOT NULL default '0',
	password varchar(100) DEFAULT '' NOT NULL,
	admin tinyint(4) unsigned DEFAULT '0' NOT NULL,
	usergroup varchar(255) DEFAULT '' NOT NULL,
	lang varchar(6) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	db_mountpoints text,
	options tinyint(4) unsigned DEFAULT '0' NOT NULL,
	realName varchar(80) DEFAULT '' NOT NULL,
	userMods text,
	allowed_languages varchar(255) DEFAULT '' NOT NULL,
	uc mediumblob,
	file_mountpoints text,
	file_permissions text,
	workspace_perms tinyint(3) DEFAULT '1' NOT NULL,
	lockToDomain varchar(50) DEFAULT '' NOT NULL,
	disableIPlock tinyint(1) unsigned DEFAULT '0' NOT NULL,
	TSconfig text,
	lastlogin int(10) unsigned DEFAULT '0' NOT NULL,
	createdByAction int(11) DEFAULT '0' NOT NULL,
	usergroup_cached_list text,
	workspace_id int(11) DEFAULT '0' NOT NULL,
	category_perms text,
	KEY username (username)
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	perms_userid int(11) unsigned DEFAULT '0' NOT NULL,
	perms_groupid int(11) unsigned DEFAULT '0' NOT NULL,
	perms_user tinyint(4) unsigned DEFAULT '0' NOT NULL,
	perms_group tinyint(4) unsigned DEFAULT '0' NOT NULL,
	perms_everybody tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	slug varchar(2048),
	doktype int(11) unsigned DEFAULT '0' NOT NULL,
	TSconfig text,
	is_siteroot tinyint(4) DEFAULT '0' NOT NULL,
	php_tree_stop tinyint(4) DEFAULT '0' NOT NULL,
	url varchar(255) DEFAULT '' NOT NULL,
	shortcut int(10) unsigned DEFAULT '0' NOT NULL,
	shortcut_mode int(10) unsigned DEFAULT '0' NOT NULL,
	subtitle varchar(255) DEFAULT '' NOT NULL,
	layout int(11) unsigned DEFAULT '0' NOT NULL,
	target varchar(80) DEFAULT '' NOT NULL,
	media int(11) unsigned DEFAULT '0' NOT NULL,
	lastUpdated int(10) unsigned DEFAULT '0' NOT NULL,
	keywords text,
	cache_timeout int(10) unsigned DEFAULT '0' NOT NULL,
	cache_tags varchar(255) DEFAULT '' NOT NULL,
	newUntil int(10) unsigned DEFAULT '0' NOT NULL,
	description text,
	no_search tinyint(3) unsigned DEFAULT '0' NOT NULL,
	SYS_LASTCHANGED int(10) unsigned DEFAULT '0' NOT NULL,
	abstract text,
	module varchar(255) DEFAULT '' NOT NULL,
	extendToSubpages tinyint(3) unsigned DEFAULT '0' NOT NULL,
	author varchar(255) DEFAULT '' NOT NULL,
	author_email varchar(255) DEFAULT '' NOT NULL,
	nav_title varchar(255) DEFAULT '' NOT NULL,
	nav_hide tinyint(4) DEFAULT '0' NOT NULL,
	content_from_pid int(10) unsigned DEFAULT '0' NOT NULL,
	mount_pid int(10) unsigned DEFAULT '0' NOT NULL,
	mount_pid_ol tinyint(4) DEFAULT '0' NOT NULL,
	alias varchar(32) DEFAULT '' NOT NULL,
	l18n_cfg tinyint(4) DEFAULT '0' NOT NULL,
	fe_login_mode tinyint(4) DEFAULT '0' NOT NULL,
	backend_layout varchar(64) DEFAULT '' NOT NULL,
	backend_layout_next_level varchar(64) DEFAULT '' NOT NULL,
	tsconfig_includes text,
	legacy_overlay_uid int(11) unsigned DEFAULT '0' NOT NULL,

	KEY alias (alias),
	KEY determineSiteRoot (is_siteroot),
	KEY language_identifier (l10n_parent,sys_language_uid),
	KEY slug (slug(127))
);

#
# Table structure for table 'sys_registry'
#
CREATE TABLE sys_registry (
	uid int(11) unsigned NOT NULL auto_increment,
	entry_namespace varchar(128) DEFAULT '' NOT NULL,
	entry_key varchar(128) DEFAULT '' NOT NULL,
	entry_value mediumblob,
	PRIMARY KEY (uid),
	UNIQUE KEY entry_identifier (entry_namespace,entry_key)
);

#
# Table structure for table 'sys_be_shortcuts'
#
CREATE TABLE sys_be_shortcuts (
	uid int(11) unsigned NOT NULL auto_increment,
	userid int(11) unsigned DEFAULT '0' NOT NULL,
	module_name varchar(255) DEFAULT '' NOT NULL,
	url text,
	description varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sc_group tinyint(4) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY event (userid)
);


#
# Table structure for table 'sys_news'
#
CREATE TABLE sys_news (
	title varchar(255) DEFAULT '' NOT NULL,
	content mediumtext
);


#
# Table structure for table 'sys_filemounts'
#
CREATE TABLE sys_filemounts (
	title varchar(255) DEFAULT '' NOT NULL,
	path varchar(255) DEFAULT '' NOT NULL,
	base int(11) unsigned DEFAULT '0' NOT NULL,
	read_only tinyint(1) unsigned DEFAULT '0' NOT NULL
);


#
# Table structure for table 'sys_file_storage'
#
CREATE TABLE sys_file_storage (
	name varchar(255) DEFAULT '' NOT NULL,
	driver tinytext,
	configuration text,
	is_default tinyint(4) DEFAULT '0' NOT NULL,
	is_browsable tinyint(4) DEFAULT '0' NOT NULL,
	is_public tinyint(4) DEFAULT '0' NOT NULL,
	is_writable tinyint(4) DEFAULT '0' NOT NULL,
	is_online tinyint(4) DEFAULT '1' NOT NULL,
	auto_extract_metadata tinyint(4) DEFAULT '1' NOT NULL,
	processingfolder tinytext
);

#
# Table structure for table 'sys_file'
#
CREATE TABLE sys_file (
	last_indexed int(11) DEFAULT '0' NOT NULL,

	# management information
	missing tinyint(4) DEFAULT '0' NOT NULL,
	storage int(11) DEFAULT '0' NOT NULL,
	type varchar(10) DEFAULT '' NOT NULL,
	metadata int(11) DEFAULT '0' NOT NULL,

	# file info data
	identifier text,
	identifier_hash char(40) DEFAULT '' NOT NULL,
	folder_hash char(40) DEFAULT '' NOT NULL,
	extension varchar(255) DEFAULT '' NOT NULL,
	mime_type varchar(255) DEFAULT '' NOT NULL,
	name tinytext,
	sha1 char(40) DEFAULT '' NOT NULL,
	size bigint(20) unsigned DEFAULT '0' NOT NULL,
	creation_date int(11) DEFAULT '0' NOT NULL,
	modification_date int(11) DEFAULT '0' NOT NULL,

	KEY sel01 (storage,identifier_hash),
	KEY folder (storage,folder_hash),
	KEY tstamp (tstamp),
	KEY lastindex (last_indexed),
	KEY sha1 (sha1)
);

#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata (
	file int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	width int(11) DEFAULT '0' NOT NULL,
	height int(11) DEFAULT '0' NOT NULL,
	description text,
	alternative text,

	KEY file (file),
	KEY fal_filelist (l10n_parent,sys_language_uid)
);


#
# Table structure for table 'sys_file_processedfile'.
# which is a "temporary" file, like an image preview
# This table does not have a TCA representation, as it is only written
# to using direct SQL queries in the code
#
CREATE TABLE sys_file_processedfile (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,

	storage int(11) DEFAULT '0' NOT NULL,
	original int(11) DEFAULT '0' NOT NULL,
	identifier varchar(512) DEFAULT '' NOT NULL,
	name tinytext,
	configuration blob,
	configurationsha1 char(40) DEFAULT '' NOT NULL,
	originalfilesha1 char(40) DEFAULT '' NOT NULL,
	task_type varchar(200) DEFAULT '' NOT NULL,
	checksum char(10) DEFAULT '' NOT NULL,
	width int(11) DEFAULT '0',
	height int(11) DEFAULT '0',

	PRIMARY KEY (uid),
	KEY combined_1 (original,task_type(100),configurationsha1),
	KEY identifier (storage,identifier(180))
);

#
# Table structure for table 'sys_file_reference'
# which is one usage of a file with overloaded metadata
#
CREATE TABLE sys_file_reference (
	# Reference fields (basically same as MM table)
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(64) DEFAULT '' NOT NULL,
	fieldname varchar(64) DEFAULT '' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,
	table_local varchar(64) DEFAULT '' NOT NULL,

	# Local usage overlay fields
	title tinytext,
	description text,
	alternative text,
	link varchar(1024) DEFAULT '' NOT NULL,
	crop varchar(4000) DEFAULT '' NOT NULL,
	autoplay tinyint(4) DEFAULT '0' NOT NULL,

	KEY tablenames_fieldname (tablenames(32),fieldname(12)),
	KEY deleted (deleted),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY combined_1 (l10n_parent, t3ver_oid, t3ver_wsid, t3ver_state, deleted)
);


#
# Table structure for table 'sys_file_collection'
#
CREATE TABLE sys_file_collection (
	title tinytext,
	type varchar(30) DEFAULT 'static' NOT NULL,

	# for type=static
	files int(11) DEFAULT '0' NOT NULL,

	# for type=folder:
	storage int(11) DEFAULT '0' NOT NULL,
	folder text,
	recursive tinyint(4) DEFAULT '0' NOT NULL,

	# for type=category:
	category int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'sys_collection'
#
CREATE TABLE sys_collection (
	title tinytext,
	type varchar(32) DEFAULT 'static' NOT NULL,
	table_name tinytext,
	items int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'sys_collection_entries'
#
CREATE TABLE sys_collection_entries (
	uid int(11) NOT NULL auto_increment,
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(64) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	PRIMARY KEY (uid)
);

#
# Table structure for table 'sys_history'
#
CREATE TABLE sys_history (
	actiontype tinyint(3) DEFAULT '0' NOT NULL,
	usertype varchar(2) DEFAULT 'BE' NOT NULL,
	userid int(11) unsigned,
	originaluserid int(11) unsigned,
	recuid int(11) DEFAULT '0' NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL,
	history_data mediumtext,
	workspace int(11) DEFAULT '0',

	KEY recordident_1 (tablename(100),recuid),
	KEY recordident_2 (tablename(100),tstamp)
) ENGINE=InnoDB;

#
# Table structure for table 'sys_lockedrecords'
#
CREATE TABLE sys_lockedrecords (
	uid int(11) unsigned NOT NULL auto_increment,
	userid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	record_table varchar(255) DEFAULT '' NOT NULL,
	record_uid int(11) DEFAULT '0' NOT NULL,
	record_pid int(11) DEFAULT '0' NOT NULL,
	username varchar(50) DEFAULT '' NOT NULL,
	feuserid int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY event (userid,tstamp)
);

#
# Table structure for table 'sys_refindex'
#
CREATE TABLE sys_refindex (
	hash varchar(32) DEFAULT '' NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL,
	recuid int(11) DEFAULT '0' NOT NULL,
	field varchar(64) DEFAULT '' NOT NULL,
	flexpointer varchar(255) DEFAULT '' NOT NULL,
	softref_key varchar(30) DEFAULT '' NOT NULL,
	softref_id varchar(40) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(1) DEFAULT '0' NOT NULL,
	workspace int(11) DEFAULT '0' NOT NULL,
	ref_table varchar(255) DEFAULT '' NOT NULL,
	ref_uid int(11) DEFAULT '0' NOT NULL,
	ref_string varchar(1024) DEFAULT '' NOT NULL,

	PRIMARY KEY (hash),
	KEY lookup_rec (tablename(100),recuid),
	KEY lookup_uid (ref_table(100),ref_uid),
	KEY lookup_string (ref_string(191))
);

#
# Table structure for table 'sys_log'
#
CREATE TABLE sys_log (
	userid int(11) unsigned DEFAULT '0' NOT NULL,
	action tinyint(4) unsigned DEFAULT '0' NOT NULL,
	recuid int(11) unsigned DEFAULT '0' NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL,
	recpid int(11) DEFAULT '0' NOT NULL,
	error tinyint(4) unsigned DEFAULT '0' NOT NULL,
	details text,
	type tinyint(3) unsigned DEFAULT '0' NOT NULL,
	details_nr tinyint(3) DEFAULT '0' NOT NULL,
	IP varchar(39) DEFAULT '' NOT NULL,
	log_data text,
	event_pid int(11) DEFAULT '-1' NOT NULL,
	workspace int(11) DEFAULT '0' NOT NULL,
	NEWid varchar(30) DEFAULT '' NOT NULL,
	request_id varchar(13) DEFAULT '' NOT NULL,
	time_micro float DEFAULT '0' NOT NULL,
	component varchar(255) DEFAULT '' NOT NULL,
	level tinyint(1) unsigned DEFAULT '0' NOT NULL,
	message text,
	data text,
	KEY event (userid,event_pid),
	KEY recuidIdx (recuid),
	KEY user_auth (type,action,tstamp),
	KEY request (request_id),
	KEY combined_1 (tstamp, type, userid)
) ENGINE=InnoDB;

#
# Table structure for table 'sys_language'
#
CREATE TABLE sys_language (
	title varchar(80) DEFAULT '' NOT NULL,
	flag varchar(20) DEFAULT '' NOT NULL,
	language_isocode varchar(2) DEFAULT '' NOT NULL,
	static_lang_isocode int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Table structure for table 'sys_category'
#
CREATE TABLE sys_category (
	title tinytext NOT NULL,
	parent int(11) DEFAULT '0' NOT NULL,
	items int(11) DEFAULT '0' NOT NULL,

	KEY category_parent (parent),
	KEY category_list (pid,deleted,sys_language_uid)
);

#
# Table structure for table 'sys_category_record_mm'
#
CREATE TABLE sys_category_record_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(255) DEFAULT '' NOT NULL,
	fieldname varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,

	KEY uid_local_foreign (uid_local,uid_foreign),
	KEY uid_foreign_tablefield (uid_foreign,tablenames(40),fieldname(3),sorting_foreign)
);
