<?php
$GLOBALS['MCA']['php'] = array (
	'general' => array (
		'title' => 'module_php_title',
	),
	
	'checks' => array (
		'version' => array (
			'title' => 'module_php_check_version_title',
			'description' => 'module_php_check_version_description',
			'categoryMain' => 'server',
			'categorySub' => 'php',
			'method' => 'php:checkVersion'
		)
	),
	
	'methods' => array (
		'phpinfo' => array (
			'title' => 'module_php_method_phpversion_title',
			'description' => 'module_php_method_phpversion_description',
			'categoryMain' => 'server',
			'categorySub' => 'php',
			'method' => 'php:getPHPInfo'
		)
	)
);
?>
