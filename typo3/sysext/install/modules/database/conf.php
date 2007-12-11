<?php
$GLOBALS['MCA']['database'] = array (
		// general settings for this module
		// This section contains things like title, description and help texts
	'general' => array (
		'title' => 'LLL:title_module_database',
	),
	
		// This sections contains all options that are provided by this module
	'options' => array (
		'typo_db_host' => array (
			'title' => 'title_typo_db_host',
			'description' => 'description_typo_db_host',
			'help' => 'help_typo_db_host',
			'categoryMain' => 'database',
			'categorySub' => 'connection',
			'tags' => array ('database', 'db', 'host'),
			'elementType' => 'input',
			'value' => 'LC:db:typo_db_host',
			'default' => 'localhost',
		),
		'typo_db_username' => array (
			'title' => 'title_typo_db_username',
			'description' => 'description_typo_db_username',
			'help' => 'help_typo_db_username',
			'categoryMain' => 'database',
			'categorySub' => 'connection',
			'tags' => array ('database', 'db', 'username', 'user'),
			'elementType' => 'input',
			'value' => 'LC:db:typo_db_username',
		),
		'typo_db_password' => array (
			'title' => 'title_typo_db_password',
			'description' => 'description_typo_db_password',
			'help' => 'help_typo_db_password',
			'categoryMain' => 'database',
			'categorySub' => 'connection',
			'tags' => array ('database', 'db', 'password'),
			'elementType' => 'input',
			'value' => 'LC:db:typo_db_password',
		),
		'typo_db' => array (
			'title' => 'title_typo_db',
			'description' => 'description_typo_db',
			'help' => 'help_typo_db',
			'categoryMain' => 'database',
			'categorySub' => 'connection',
			'tags' => array ('database', 'db'),
			'elementType' => 'selectbox',
			'value' => 'LC:db:typo_db',
			'overruleOptions' => array (
				'elements' => 'database:getDatabaseList',
				'empty' => '&nbsp;'
			),
		),
		'typo_db_new' => array (
			'title' => 'title_typo_db_new',
			'description' => 'description_typo_db_new',
			'help' => 'help_typo_db_new',
			'categoryMain' => 'database',
			'categorySub' => 'connection',
			'tags' => array ('database', 'db', 'username', 'user'),
			'elementType' => 'input',
			'value' => 'LC:db:typo_db',
		),
	),
	
	'methods' => array (
		'analyze_compareFile' => array (
			'categoryMain' => 'database',
			'categorySub' => 'analyze',
			'tags' => array('database', 'db', 'cleanup', 'compare'),
			'method' => 'database:analyzeCompareFile',
			'autostart' => false
		),
		'cleanUp_cachedImageSizes' => array(
			'title' => 'title_cleanUp_cachedImageSizes',
			'description' => 'description_cleanUp_cachedImageSizes',
			'help' => 'help_cleanUp_cachedImageSizes',
			'categoryMain' => 'database',
			'categorySub' => 'cleanup',
			'tags' => array ('database', 'db', 'clean'),
			'method' => 'database:cleanUpCachedImageSizes',
			'autostart' => false
		),
	),
	
		// this section contains all provided checks that this module provides
	'checks' => array (
		'database_connectable' => array (
			'title' => 'title_database_connectable',
			'categoryMain' => 'database',
			'categorySub' => 'connection',
			'tags' => array('database', 'db', 'connection'),
			'method' => 'database:checkDatabaseConnect'
		),
	)
);
?>
