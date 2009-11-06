<?php
/**
 * Oracle configuration
 * 
 * $Id$
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
global $TYPO3_CONF_VARS;

$TYPO3_CONF_VARS['EXTCONF']['dbal']['handlerCfg'] = array(
	'_DEFAULT' => array( 
		'type' => 'adodb', 
		'config' => array(
			'driver' => 'oci8',
		),
	), 
);

$TYPO3_CONF_VARS['EXTCONF']['dbal']['mapping'] = array(
	'cachingframework_cache_hash' => array(
		'mapTableName' => 'cf_cache_hash',
	),
	'tx_templavoila_datastructure' => array(
		'mapTableName' => 'tx_templavoila_ds',
		'mapFieldNames' => array(
			'dataprot' => 'my_dataprot',
			'title' => 'my_title',
		),
	),
	'tt_news' => array(
		'mapTableName' => 'XP_tt_news',
		'mapFieldNames' => array(
			'uid' => 'XP_uid',
		),
	),
	'tt_news_cat' => array(
		'mapTableName' => 'XP_tt_news_cat',
		'mapFieldNames' => array(
			'uid' => 'XP_uid',
		),
	),
	'tt_news_cat_mm' => array(
		'mapTableName' => 'XP_tt_news_cat_mm',
		'mapFieldNames' => array(
			'uid_local' => 'XP_uid_local',
			'uid_foreign' => 'XP_uid_foreign',
		),
	),
);
?>