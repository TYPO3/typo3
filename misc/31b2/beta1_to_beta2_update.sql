alter table tt_content add imageheight mediumint unsigned default 0 not null;
alter table cache_hash add ident varchar(20) default "" not null;
drop table cache_pages;
CREATE TABLE cache_pages (
  id int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  hash varchar(32) DEFAULT '' NOT NULL,
  page_id int(11) unsigned DEFAULT '0' NOT NULL,
  HTML mediumblob NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  expires int(10) unsigned DEFAULT '0' NOT NULL,
  data blob NOT NULL,
  KEY page_id (page_id),
  KEY sel (hash,page_id),
  PRIMARY KEY (id)
);
alter table pages change cache_timeout cache_timeout int unsigned default 0 not null;
alter table pages add SYS_LASTCHANGED int unsigned default 0 not null;
alter table fe_users add uc blob default '' not null;
CREATE TABLE fe_session_data (
  hash varchar(32) DEFAULT '' NOT NULL,
  content blob NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (hash)
);
alter table sys_stat add flags tinyint unsigned default 0 not null;
alter table fe_groups add description text default '' not null;
alter table sys_domain add redirectTo varchar(120) default '' not null;
