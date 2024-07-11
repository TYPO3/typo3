# Define table and fields since it has no TCA
CREATE TABLE be_sessions (
	ses_id varchar(190) DEFAULT '' NOT NULL,
	ses_iplock varchar(39) DEFAULT '' NOT NULL,
	ses_userid int(11) unsigned DEFAULT '0' NOT NULL,
	ses_tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	ses_data longblob,

	PRIMARY KEY (ses_id),
	KEY ses_tstamp (ses_tstamp)
);

CREATE TABLE be_users (
	# @todo: Analyzer does not handle default yet.
	lang varchar(10) DEFAULT 'default' NOT NULL,
	# No TCA column defined since it is a general storage blob
	uc mediumblob,
	# No TCA column defined
	workspace_id int(11) DEFAULT '0' NOT NULL,
	# @todo: Keep this field defined here or make it a different type (not 'none') in TCA and handle in schema analyzer
	mfa mediumblob,

	KEY username (username)
);

CREATE TABLE be_groups(
	# @todo: Remove once tables_modify and tables_select are merged to one field
	tables_select longtext
);

CREATE TABLE pages (
	# No TCA column defined for perms_
	perms_userid int(11) unsigned DEFAULT '0' NOT NULL,
	perms_groupid int(11) unsigned DEFAULT '0' NOT NULL,
	perms_user tinyint(4) unsigned DEFAULT '0' NOT NULL,
	perms_group tinyint(4) unsigned DEFAULT '0' NOT NULL,
	perms_everybody tinyint(4) unsigned DEFAULT '0' NOT NULL,
	# No TCA column defined
	SYS_LASTCHANGED int(10) unsigned DEFAULT '0' NOT NULL,
	# @todo: type=group fields, but rely on integer.
	shortcut int(10) unsigned DEFAULT '0' NOT NULL,
	content_from_pid int(10) unsigned DEFAULT '0' NOT NULL,
	mount_pid int(10) unsigned DEFAULT '0' NOT NULL,

	KEY determineSiteRoot (is_siteroot),
	KEY language_identifier (l10n_parent,sys_language_uid),
	KEY slug (slug(127))
);

# Define table and fields since it has no TCA
CREATE TABLE sys_registry (
	uid int(11) unsigned NOT NULL auto_increment,
	entry_namespace varchar(128) DEFAULT '' NOT NULL,
	entry_key varchar(128) DEFAULT '' NOT NULL,
	entry_value mediumblob,

	PRIMARY KEY (uid),
	UNIQUE KEY entry_identifier (entry_namespace,entry_key)
);

# Define table and fields since it has no TCA
CREATE TABLE sys_be_shortcuts (
	uid int(11) unsigned NOT NULL auto_increment,
	userid int(11) unsigned DEFAULT '0' NOT NULL,
	route varchar(255) DEFAULT '' NOT NULL,
	arguments text,
	description varchar(255) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	sc_group tinyint(4) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY event (userid)
);

CREATE TABLE sys_file_storage (
	# @todo: type=user currently needs manual configuration
	is_public tinyint(4) DEFAULT '0' NOT NULL,
	# @todo: This can be a varchar(255), but it needs clarification if it can be nullable.
	processingfolder tinytext
);

CREATE TABLE sys_file (
	# No TCA column
	last_indexed int(11) DEFAULT '0' NOT NULL,
	# @todo: Incomplete or broken TCA
	identifier text,
	# No TCA column
	identifier_hash char(40) DEFAULT '' NOT NULL,
	# No TCA column
	folder_hash char(40) DEFAULT '' NOT NULL,
	# No TCA column
	extension varchar(255) DEFAULT '' NOT NULL,
	# @todo: Restrict to varchar(255)?
	name tinytext,
	# No TCA column
	sha1 char(40) DEFAULT '' NOT NULL,
	# No TCA column
	creation_date int(11) DEFAULT '0' NOT NULL,
	# No TCA column
	modification_date int(11) DEFAULT '0' NOT NULL,

	KEY sel01 (storage,identifier_hash),
	KEY folder (storage,folder_hash),
	KEY tstamp (tstamp),
	KEY lastindex (last_indexed),
	KEY sha1 (sha1)
);

CREATE TABLE sys_file_metadata (
	# @todo: Restrict to varchar(255)?
	title tinytext,
	# @todo: Restrict to varchar(255)?
	alternative text,

	KEY file (file),
	KEY fal_filelist (l10n_parent,sys_language_uid)
);

# Define table and fields since it has no TCA
CREATE TABLE sys_file_processedfile (
	uid int(11) NOT NULL auto_increment,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	storage int(11) DEFAULT '0' NOT NULL,
	original int(11) DEFAULT '0' NOT NULL,
	identifier varchar(512) DEFAULT '' NOT NULL,
	name tinytext,
	processing_url text,
	configuration blob,
	configurationsha1 char(40) DEFAULT '' NOT NULL,
	originalfilesha1 char(40) DEFAULT '' NOT NULL,
	task_type varchar(200) DEFAULT '' NOT NULL,
	checksum char(32) DEFAULT '' NOT NULL,
	width int(11) DEFAULT '0',
	height int(11) DEFAULT '0',

	PRIMARY KEY (uid),
	KEY combined_1 (original,task_type(100),configurationsha1),
	KEY identifier (storage,identifier(180))
);

CREATE TABLE sys_file_reference (
	# @todo: type=group field, but rely on integer.
	uid_local int(11) DEFAULT '0' NOT NULL,
	# @todo: Restrict to varchar(255)?
	title tinytext,
	# @todo: Restrict to varchar(255)?
	alternative text,

	KEY tablenames_fieldname (tablenames(32),fieldname(12)),
	KEY deleted (deleted),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY combined_1 (l10n_parent, t3ver_oid, t3ver_wsid, t3ver_state, deleted)
);

CREATE TABLE sys_file_collection (
	# @todo: Restrict to varchar(255)?
	title tinytext,
	# @todo: db analyzer would remove default. needs another look.
	type varchar(30) DEFAULT 'static' NOT NULL,
);

# Define table and fields since it has no TCA
CREATE TABLE sys_history (
	uid int(11) unsigned NOT NULL auto_increment,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	actiontype tinyint(3) DEFAULT '0' NOT NULL,
	usertype varchar(2) DEFAULT 'BE' NOT NULL,
	userid int(11) unsigned,
	originaluserid int(11) unsigned,
	recuid int(11) DEFAULT '0' NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL,
	history_data mediumtext,
	workspace int(11) DEFAULT '0',
	correlation_id varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY recordident_1 (tablename(100),recuid),
	KEY recordident_2 (tablename(100),tstamp)
) ENGINE=InnoDB;

# Define table and fields since it has no TCA
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

# Define table and fields since it has no TCA
CREATE TABLE sys_refindex (
	# @todo: Force a latin1 field to reduce primary key length, it only holds hex chars 0-9,a-f.
	hash varchar(32) DEFAULT '' NOT NULL,
	tablename varchar(64) DEFAULT '' NOT NULL,
	recuid int unsigned DEFAULT 0 NOT NULL,
	field varchar(64) DEFAULT '' NOT NULL,
	hidden smallint unsigned DEFAULT 0 NOT NULL,
	starttime int unsigned DEFAULT 0 NOT NULL,
	# @todo: 2^31-1 (year 2038) and not 2^32-1 since postgres 32-bit int is always signed
	endtime int unsigned DEFAULT 2147483647 NOT NULL,
	t3ver_state int unsigned DEFAULT 0 NOT NULL,
	flexpointer varchar(255) DEFAULT '' NOT NULL,
	softref_key varchar(30) DEFAULT '' NOT NULL,
	softref_id varchar(40) DEFAULT '' NOT NULL,
	# @todo: not unsigned since refindex wrote -1 for _STRING rows until v13.2.
	#        Set unsigned in v14 or have an upgrade wizard in v13?
	sorting int DEFAULT 0 NOT NULL,
	workspace int unsigned DEFAULT 0 NOT NULL,
	ref_table varchar(64) DEFAULT '' NOT NULL,
	# @todo: ref_uid is still signed since refindex tends to write -2 for fe_group "all" relations.
	#        EidRequestTest.php PlainScenario.yaml triggers this and fails with mariadb.
	#        This is about "not real db relations" in refindex and needs to be sorted out
	#        including some dedicated tests.
	ref_uid int DEFAULT 0 NOT NULL,
	ref_field varchar(64) DEFAULT '' NOT NULL,
	ref_hidden smallint unsigned DEFAULT 0 NOT NULL,
	ref_starttime int unsigned DEFAULT 0 NOT NULL,
	# @todo: 2^31-1 (year 2038) and not 2^32-1 since postgres 32-bit int is always signed
	ref_endtime int unsigned DEFAULT 2147483647 NOT NULL,
	ref_t3ver_state int unsigned DEFAULT 0 NOT NULL,
	ref_sorting int DEFAULT 0 NOT NULL,
	ref_string varchar(1024) DEFAULT '' NOT NULL,

	PRIMARY KEY (hash),
	# These two indexes are optimized for FE RootlineUtility usage. Other queries often at least re-use
	# the first parts of the combined index, or can be changed to include more dummy where parts to use even more.
	KEY lookup_rec (tablename,recuid,field,workspace,ref_t3ver_state,ref_hidden,ref_starttime,ref_endtime),
	KEY lookup_ref (ref_table,ref_uid,tablename,workspace,t3ver_state,hidden,starttime,endtime),
);

# Define table and fields since it has no TCA
CREATE TABLE sys_log (
	uid int(11) unsigned NOT NULL auto_increment,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	userid int(11) unsigned DEFAULT '0' NOT NULL,
	action tinyint(4) unsigned DEFAULT '0' NOT NULL,
	recuid int(11) unsigned DEFAULT '0' NOT NULL,
	tablename varchar(255) DEFAULT '' NOT NULL,
	recpid int(11) DEFAULT '0' NOT NULL,
	error tinyint(4) unsigned DEFAULT '0' NOT NULL,
	details text,
	type tinyint(3) unsigned DEFAULT '0' NOT NULL,
	channel varchar(20) DEFAULT 'default' NOT NULL,
	details_nr tinyint(3) DEFAULT '0' NOT NULL,
	IP varchar(39) DEFAULT '' NOT NULL,
	log_data text,
	event_pid int(11) DEFAULT '-1' NOT NULL,
	workspace int(11) DEFAULT '0' NOT NULL,
	NEWid varchar(30) DEFAULT '' NOT NULL,
	request_id varchar(13) DEFAULT '' NOT NULL,
	time_micro float DEFAULT '0' NOT NULL,
	component varchar(255) DEFAULT '' NOT NULL,
	level varchar(10) DEFAULT 'info' NOT NULL,
	message text,
	data text,

	PRIMARY KEY (uid),
	KEY event (userid, event_pid),
	KEY recuidIdx (recuid),
	KEY user_auth (type, action, tstamp),
	KEY request (request_id),
	KEY combined_1 (tstamp, type, userid),
	KEY errorcount (tstamp, error),
	KEY index_channel (channel),
	KEY index_level (level)
) ENGINE=InnoDB;

CREATE TABLE sys_category (
	# @todo: type=group fields, but rely on integer.
	items int(11) DEFAULT '0' NOT NULL,

	KEY category_parent (parent),
	KEY category_list (pid,deleted,sys_language_uid)
);

# Define table and fields since it has no TCA
CREATE TABLE `sys_messenger_messages` (
	id int(11) unsigned NOT NULL auto_increment,
	body longtext NOT NULL,
	headers longtext NOT NULL,
	queue_name varchar(190) NOT NULL,
	created_at datetime NOT NULL,
	available_at datetime NOT NULL,
	delivered_at datetime DEFAULT NULL,

	PRIMARY KEY (id),
	KEY queue_name (queue_name),
	KEY available_at (available_at),
	KEY delivered_at (delivered_at)
) ENGINE=InnoDB;

# Define table and fields since it has no TCA
CREATE TABLE sys_http_report (
	uuid varchar(36) NOT NULL,
	status tinyint(1) unsigned DEFAULT '0' NOT NULL,
	created int(11) unsigned NOT NULL,
	changed int(11) unsigned NOT NULL,
	type varchar(32) NOT NULL,
	scope varchar(32) NOT NULL,
	request_time bigint(20) unsigned NOT NULL,
	meta mediumtext,
	details mediumtext,
	summary varchar(40) NOT NULL,

	PRIMARY KEY (uuid),
	KEY type_scope (type,scope),
	KEY created (created),
	KEY changed (changed),
	KEY request_time (request_time),
	KEY summary_created (summary,created),
	KEY all_conditions (type,status,scope,summary,request_time)
) ENGINE=InnoDB;

# Define table and fields since it has no TCA
CREATE TABLE sys_csp_resolution (
	summary varchar(40) NOT NULL,
	created int(11) unsigned NOT NULL,
	scope varchar(264) NOT NULL,
	mutation_identifier text,
	mutation_collection mediumtext,
	meta mediumtext,

	PRIMARY KEY (summary),
	KEY created (created),
) ENGINE=InnoDB;
