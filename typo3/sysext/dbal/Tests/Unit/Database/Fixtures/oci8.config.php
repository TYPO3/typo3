<?php
/**
 * Oracle configuration
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
global $TYPO3_CONF_VARS;
$TYPO3_CONF_VARS['EXTCONF']['dbal']['handlerCfg'] = array(
	'_DEFAULT' => array(
		'type' => 'adodb',
		'config' => array(
			'driver' => 'oci8'
		)
	)
);
$TYPO3_CONF_VARS['EXTCONF']['dbal']['mapping'] = array(
	'cachingframework_cache_hash' => array(
		'mapTableName' => 'cf_cache_hash'
	),
	'cachingframework_cache_hash_tags' => array(
		'mapTableName' => 'cf_cache_hash_tags'
	),
	'cachingframework_cache_pages' => array(
		'mapTableName' => 'cf_cache_pages'
	),
	'cpg_categories' => array(
		'mapFieldNames' => array(
			'pid' => 'page_id'
		)
	),
	'pages' => array(
		'mapTableName' => 'my_pages',
		'mapFieldNames' => array(
			'uid' => 'page_uid'
		)
	),
	'tt_news' => array(
		'mapTableName' => 'ext_tt_news',
		'mapFieldNames' => array(
			'uid' => 'news_uid',
			'fe_group' => 'usergroup'
		)
	),
	'tt_news_cat' => array(
		'mapTableName' => 'ext_tt_news_cat',
		'mapFieldNames' => array(
			'uid' => 'cat_uid'
		)
	),
	'tt_news_cat_mm' => array(
		'mapTableName' => 'ext_tt_news_cat_mm',
		'mapFieldNames' => array(
			'uid_local' => 'local_uid'
		)
	),
	'tx_crawler_process' => array(
		'mapTableName' => 'tx_crawler_ps',
		'mapFieldNames' => array(
			'process_id' => 'ps_id',
			'active' => 'is_active'
		)
	),
	'tx_dam_file_tracking' => array(
		'mapFieldNames' => array(
			'file_name' => 'filename',
			'file_path' => 'path'
		)
	),
	'tx_dbal_debuglog' => array(
		'mapFieldNames' => array(
			'errorFlag' => 'errorflag'
		)
	),
	'tx_templavoila_datastructure' => array(
		'mapTableName' => 'tx_templavoila_ds'
	)
);
?>